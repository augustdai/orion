<?php

namespace Orion\Stone;

use ReflectionClass, ReflectionParameter;
use Closure, ArrayAccess;

use Orion\Contracts\BindingResolutionException;
use Orion\Log\LogServiceProvider;

/**
* Application
*/
class Application implements ArrayAccess
{
    public $path;
    protected $bindings = [];
    protected $resolved = [];
    protected $buildStack = [];
    protected $instances = [];
    public $contextual = [];
    protected $hasBeenBootstrapped = false;

    /**
     * Application constructor.
     * @param null $path
     */
    public function __construct($path = null)
    {
        $this->path = $path;
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        // $this->registerCoreContainerAliases();
    }

    /**
     *
     */
    protected function registerBaseBindings()
    {
        $this->instances['app'] = $this;
    }

    /**
     * @return mixed
     */
    protected function registerBaseServiceProviders()
    {
        $provider = $this->resolveProvider(new LogServiceProvider($this));
        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        $this->markAsRegistered($provider);
        return $provider;
    }

    /**
     * @param $provider
     */
    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * @param $provider
     * @return mixed
     */
    public function resolveProvider($provider)
    {
    return new $provider($this);
    }

    /**
     * @param $abstract
     * @param null $concrete
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * @param $abstract
     * @param null $concrete
     * @param bool $shared
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (! $concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * @param $abstract
     * @param $concrete
     * @return Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            $method = ($abstract == $concrete) ? 'build' : 'make';

            return $container->$method($concrete, $parameters);
        };
    }

    /**
     * @param $abstract
     * @param array $parameters
     * @return mixed|object
     * @throws \ReflectionException
     */
    public function make($abstract, array $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        $this->resolved[$abstract] = true;
        return $object;
    }

    /**
     * @param $concrete
     * @param array $parameters
     * @return mixed|object
     * @throws \ReflectionException
     */
    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        $reflector = new ReflectionClass($concrete);
        $this->buildStack[] = $concrete;
        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete;
        }
        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $parameters = $this->keyParametersByArgument(
            $dependencies, $parameters
        );
        $instances = $this->getDependencies(
            $dependencies, $parameters
        );
        array_pop($this->buildStack);
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * @param array $dependencies
     * @param array $parameters
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);

                $parameters[$dependencies[$key]->name] = $value;
            }
        }
        return $parameters;
    }

    /**
     * @param array $parameters
     * @param array $primitives
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->name))) {
            if ($concrete instanceof Closure) {
                return call_user_func($concrete, $this);
            } else {
                return $concrete;
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed|object
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        }

        // If we can not resolve the class instance, we will check to see if the value
        // is optional, and if it is we will return the optional parameter value as
        // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    /**
     * @param $abstract
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        if (! is_null($concrete = $this->getContextualConcrete($abstract))) {
            return $concrete;
        }

        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (! isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * @param $abstract
     * @return mixed
     */
    protected function getContextualConcrete($abstract)
    {
        if (isset($this->contextual[end($this->buildStack)][$abstract])) {
            return $this->contextual[end($this->buildStack)][$abstract];
        }
    }

    /**
     * @param $concrete
     * @param $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * @param $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        if (isset($this->instances[$abstract])) {
            return true;
        }
        if (! isset($this->bindings[$abstract]['shared'])) {
            return false;
        }
        return $this->bindings[$abstract]['shared'] === true;
    }

    /**
     * @param array $bootstrappers
     * @throws \ReflectionException
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;
        foreach ($bootstrappers as $bootstrapper) {
            // $this['events']->fire('bootstrapping: '.$bootstrapper, [$this]);
            $this->make($bootstrapper)->bootstrap($this);
            // $this['events']->fire('bootstrapped: '.$bootstrapper, [$this]);
        }
    }

    /**
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * @param mixed $key
     * @return mixed|object
     * @throws \ReflectionException
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        // If the value is not a Closure, we will make it one. This simply gives
        // more "drop-in" replacement functionality for the Pimple which this
        // container's simplest functions are base modeled and built after.
        if (! $value instanceof Closure) {
            $value = function () use ($value) {
                return $value;
            };
        }
        $this->bind($key, $value);
    }

    /**
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        $key = $this->normalize($key);
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

}