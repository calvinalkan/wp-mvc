<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Contracts\ObjectCopier;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Contracts\CustomizablePayload;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;
use Snicco\EventDispatcher\Contracts\DispatchesConditionally;
use Snicco\EventDispatcher\Implementations\NativeObjetCopier;
use Snicco\EventDispatcher\Exceptions\InvalidListenerException;
use Snicco\EventDispatcher\Exceptions\UnremovableListenerException;

use function has_filter;
use function apply_filters;
use function Snicco\EventDispatcher\functions\validatedListener;
use function Snicco\EventDispatcher\functions\isWildCardEventListener;
use function Snicco\EventDispatcher\functions\getTypeHintedEventFromClosure;
use function Snicco\EventDispatcher\functions\wildcardPatternMatchesEventName;

final class EventDispatcher implements Dispatcher
{
    
    /**
     * Internal interfaces that should be used as a listener alias.
     *
     * @var string[]
     */
    private array $internal_interfaces = [
        CustomizablePayload::class,
        DispatchesConditionally::class,
        Event::class,
        IsForbiddenToWordPress::class,
        Mutable::class,
    ];
    
    /**
     * All the registered events keyed by event name
     *
     * @var array<string,array>
     */
    private array $listeners = [];
    
    /**
     * All listeners that are marked as unremovable keyed by event name
     *
     * @var array<string,array>
     */
    private array $unremovable = [];
    
    /**
     * The listener factory will be responsible for instantiating a matching listener.
     *
     * @var ListenerFactory
     */
    private ListenerFactory $listener_factory;
    
    /**
     * Used the make immutable copies of event objects. By default, the native "clone" function
     * will be used. If your event objects contain public properties that are in and of themselves
     * object you should consider using something like: https://github.com/myclabs/DeepCopy
     *
     * @var ObjectCopier
     */
    private ObjectCopier $object_copier;
    
    /**
     * Cache of the matching listeners keyed by event name. Used so that we don't have to match
     * again if a wildcard event were to be dispatched a second time.
     * The cache is reset everytime a new event is added.
     *
     * @var array<string,array>
     */
    private array $wildcards_listeners_cache = [];
    
    /**
     * Array of the wildcard listeners keyed by the wildcard pattern.
     *
     * @var array<string,array<Closure>>
     */
    private array $wildcard_listeners = [];
    
    /**
     * @param  ListenerFactory  $listener_factory
     * @param  ObjectCopier|null  $object_copier
     */
    public function __construct(ListenerFactory $listener_factory, ?ObjectCopier $object_copier = null)
    {
        $this->listener_factory = $listener_factory;
        $this->object_copier = $object_copier ?? new NativeObjetCopier();
    }
    
    /**
     * @param  string|Closure  $event_name  If  the event name is a closure the event will be
     * retrieved from the first closure parameter.
     * @param  array|string|Closure  $listener  Array: [class, method].
     * string: A class that either has a handle method or the __invoke method defined.
     * Closure: The closure will be used as is. No additional dependencies will be passed to the
     *     closure besides the event object.
     * @param  bool  $can_be_removed
     *
     * @throws InvalidListenerException|InvalidArgumentException|ReflectionException
     * @api
     */
    public function listen($event_name, $listener = null, bool $can_be_removed = true)
    {
        if ($event_name instanceof Closure) {
            $this->listen(getTypeHintedEventFromClosure($event_name), $event_name);
            return;
        }
        $listener = validatedListener($listener);
        
        if (isWildCardEventListener($event_name)) {
            $this->registerWildcardListener($event_name, $listener);
            return;
        }
        
        if ($listener instanceof Closure) {
            // Closure listeners can not be removed.
            $this->listeners[$event_name][] = $listener;
            return;
        }
        else {
            $this->listeners[$event_name][$listener[0]] = $listener;
        }
        
        if ( ! $can_be_removed) {
            $this->unremovable[$event_name][$listener[0]] = $listener[0];
        }
    }
    
    /**
     * @param  string|Event  $event
     * @param  array  $payload
     *
     * @return Event
     * @api
     */
    public function dispatch($event, ...$payload) :Event
    {
        [$event_name, $event] = $this->getEventAndPayload($event, $payload);
        
        if ( ! $this->shouldDispatch($event)) {
            return $event;
        }
        
        foreach ($this->getListenersForEvent($event_name) as $listener) {
            $this->callListener(
                $listener,
                $this->getPayloadForCurrentIteration($event),
                $event_name
            );
        }
        
        if ($event instanceof IsForbiddenToWordPress) {
            return $event;
        }
        
        if ( ! has_filter($event_name)) {
            return $event;
        }
        
        if ($event instanceof Mutable) {
            // Don't return the returned value of apply_filters() since third party devs might return something completely wrong.
            // Since our event is mutable it is passed by reference here. Developers can manipulate the event object directly
            // within our defined constraints.
            apply_filters($event_name, $event);
        }
        else {
            // Make an immutable copy of the event. Developers can interact with this event object the same
            // way as with the original event expect that public properties are read only.
            do_action($event_name, new ImmutableEvent($event));
        }
        return $event;
    }
    
    /**
     * @note Closure listeners can't be removed.
     *
     * @param  string  $event_name
     * @param  string|null  $listener_class
     *
     * @throws UnremovableListenerException
     * @api
     */
    public function remove(string $event_name, string $listener_class = null)
    {
        if (is_null($listener_class)) {
            unset($this->listeners[$event_name]);
            return;
        }
        
        if (isset($this->listeners[$event_name])
            && isset($this->listeners[$event_name][$listener_class])) {
            if (isset($this->unremovable[$event_name][$listener_class])) {
                throw UnremovableListenerException::becauseTheDeveloperTriedToRemove(
                    $listener_class,
                    $event_name
                );
            }
            
            unset($this->listeners[$event_name][$listener_class]);
        }
    }
    
    /**
     * @param $event_name
     * @param  array  $payload
     *
     * @return array<string,Event>
     * @interal
     */
    public function getEventAndPayload($event_name, array $payload) :array
    {
        if ($event_name instanceof Event) {
            return [get_class($event_name), $event_name];
        }
        
        return [$event_name, new GenericEvent($payload)];
    }
    
    private function getListenersForEvent(string $event_name) :array
    {
        $listeners = $this->listeners[$event_name] ?? [];
        
        $listeners = array_merge($listeners, $this->getWildCardListeners($event_name));
        
        if ( ! class_exists($event_name)) {
            return $listeners;
        }
        
        foreach ((array) class_implements($event_name) as $interface) {
            if (in_array($interface, $this->internal_interfaces, true)) {
                continue;
            }
            $listeners = array_merge($listeners, $this->getListenersForEvent($interface));
        }
        
        $parent = get_parent_class($event_name);
        
        if ( ! $parent || ! (new ReflectionClass($parent))->isAbstract()) {
            return $listeners;
        }
        
        return array_merge($listeners, $this->getListenersForEvent($parent));
    }
    
    private function callListener($listener, Event $event, string $event_name) :void
    {
        $this->listener_factory->create($listener, $event_name)
                               ->call($event, $event_name);
    }
    
    private function getPayloadForCurrentIteration(Event $payload) :Event
    {
        return $payload instanceof Mutable ? $payload : $this->object_copier->copy($payload);
    }
    
    private function shouldDispatch(Event $event) :bool
    {
        return $event instanceof DispatchesConditionally ? $event->shouldDispatch() : true;
    }
    
    private function registerWildcardListener(string $event_name, $listener)
    {
        $this->wildcard_listeners[$event_name][] = function (...$payload) use ($listener) {
            // For wildcard listeners we want to pass the event name as the first argument.
            $event_name = array_pop($payload);
            array_unshift($payload, $event_name);
            
            return call_user_func_array($listener, $payload);
        };
        
        $this->wildcards_listeners_cache = [];
    }
    
    private function getWildCardListeners(string $event_name) :array
    {
        if (isset($this->wildcards_listeners_cache[$event_name])) {
            return $this->wildcards_listeners_cache[$event_name] ?? [];
        }
        
        $wildcards = [];
        
        foreach ($this->wildcard_listeners as $wildcard_pattern => $listeners) {
            if (wildcardPatternMatchesEventName($wildcard_pattern, $event_name)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }
        
        return $this->wildcards_listeners_cache[$event_name] = $wildcards;
    }
    
}