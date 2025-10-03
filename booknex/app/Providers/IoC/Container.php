<?php

namespace BookneticApp\Providers\IoC;

use BookneticApp\Providers\IoC\Exceptions\CannotAutoWireBuiltinTypeException;
use BookneticApp\Providers\IoC\Exceptions\CannotResolveParameterException;
use BookneticApp\Providers\IoC\Exceptions\ServiceNotFoundException;
use BookneticApp\Providers\IoC\Exceptions\UnknownServiceLifetimeException;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;

class Container
{
    private static self $instance;
    private array $services = [];
    private array $instances = [];
    private array $scopedInstances = [];
    private array $lifetimes = [];
    private int $currentScope = 0;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function add( string $id, $factory = null ): void
    {
        self::getInstance()->register( $id, $factory, ServiceLifetime::SINGLETON );
    }

    public static function addScoped( string $id, $factory = null ): void
    {
        self::getInstance()->register( $id, $factory, ServiceLifetime::SCOPED );
    }

    public function addTransient( string $id, $factory = null ): void
    {
        self::getInstance()->register( $id, $factory, ServiceLifetime::TRANSIENT );
    }

    private function register( string $id, $factory, string $lifetime ): void
    {
        if ( $factory === null ) {
            // If no factory provided, use the class itself as the ID
            $this->services[ $id ] = $id;
        } else {
            $this->services[ $id ] = $factory;
        }
        $this->lifetimes[ $id ] = $lifetime;
    }

    public function beginScope(): int
    {
        return ++$this->currentScope;
    }

    public function endScope( int $scope ): void
    {
        if ( isset( $this->scopedInstances[ $scope ] ) ) {
            unset( $this->scopedInstances[ $scope ] );
        }
    }

    /**
     * @template TClass of object
     *
     * @param class-string<TClass> $id
     * @return TClass
     * */
    public static function get( string $id ): object
    {
        $instance = self::getInstance();

        // If the service doesn't exist, throw an exception
        if ( ! isset( $instance->services[ $id ] ) ) {
            throw new ServiceNotFoundException( $id );
        }

        $lifetime = $instance->lifetimes[ $id ];

        switch ( $lifetime ) {
            case ServiceLifetime::SINGLETON:
                return $instance->getSingleton( $id );
            case ServiceLifetime::SCOPED:
                return $instance->getScoped( $id );
            case ServiceLifetime::TRANSIENT:
                return $instance->createInstance( $id );
            default:
                throw new UnknownServiceLifetimeException( $lifetime );
        }
    }

    private function getSingleton( string $id ): object
    {
        return $this->instances[ $id ] ??= $this->createInstance( $id );
    }

    private function createInstance( string $id ): object
    {
        // If it's a factory, use it
        if ( is_callable( $this->services[ $id ] ) ) {
            return $this->services[ $id ]( $this );
        }

        // Otherwise, try to instantiate the class with its dependencies
        return $this->resolve( $this->services[ $id ] );
    }

    /**
     * @throws \ReflectionException
     */
    private function resolve( string $className ): object
    {
        $reflectionClass = new ReflectionClass( $className );

        if ( ! $reflectionClass->isInstantiable() ) {
            throw new RuntimeException( "Class $className is not instantiable" );
        }

        $constructor = $reflectionClass->getConstructor();

        if ( $constructor === null ) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies( $parameters );

        return $reflectionClass->newInstanceArgs( $dependencies );
    }

    private function resolveDependencies( array $parameters ): array
    {
        return array_map( function ( ReflectionParameter $parameter ) {
            $type = $parameter->getType();

            if ( $type === null ) {
                if ( $parameter->isDefaultValueAvailable() ) {
                    return $parameter->getDefaultValue();
                }
                throw new CannotResolveParameterException( $parameter->getName() );
            }

            $typeName = $type->getName();

            if ( $type->isBuiltin() ) {
                if ( $parameter->isDefaultValueAvailable() ) {
                    return $parameter->getDefaultValue();
                }

                throw new CannotAutoWireBuiltinTypeException( $typeName, $parameter->getName() );
            }

            return $this->get( $typeName );
        }, $parameters );
    }

    private function getScoped( string $id ): object
    {
        if ( ! isset( $this->scopedInstances[ $this->currentScope ][ $id ] ) ) {
            $this->scopedInstances[ $this->currentScope ][ $id ] = $this->createInstance( $id );
        }

        return $this->scopedInstances[ $this->currentScope ][ $id ];
    }
}
