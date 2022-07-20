<?php

namespace Container17UAd7t;
include_once \dirname(__DIR__, 4).'/vendor/doctrine/persistence/lib/Doctrine/Persistence/ObjectManager.php';
include_once \dirname(__DIR__, 4).'/vendor/doctrine/orm/lib/Doctrine/ORM/EntityManagerInterface.php';
include_once \dirname(__DIR__, 4).'/vendor/doctrine/orm/lib/Doctrine/ORM/EntityManager.php';

class EntityManager_9a5be93 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager|null wrapped object, if the proxy is initialized
     */
    private $valueHolderfa6bc = null;

    /**
     * @var \Closure|null initializer responsible for generating the wrapped object
     */
    private $initializerc716d = null;

    /**
     * @var bool[] map of public properties of the parent class
     */
    private static $publicPropertiesd7c1a = [
        
    ];

    public function getConnection()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getConnection', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getConnection();
    }

    public function getMetadataFactory()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getMetadataFactory', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getMetadataFactory();
    }

    public function getExpressionBuilder()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getExpressionBuilder', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getExpressionBuilder();
    }

    public function beginTransaction()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'beginTransaction', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->beginTransaction();
    }

    public function getCache()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getCache', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getCache();
    }

    public function transactional($func)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'transactional', array('func' => $func), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->transactional($func);
    }

    public function wrapInTransaction(callable $func)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'wrapInTransaction', array('func' => $func), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->wrapInTransaction($func);
    }

    public function commit()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'commit', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->commit();
    }

    public function rollback()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'rollback', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->rollback();
    }

    public function getClassMetadata($className)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getClassMetadata', array('className' => $className), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getClassMetadata($className);
    }

    public function createQuery($dql = '')
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'createQuery', array('dql' => $dql), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->createQuery($dql);
    }

    public function createNamedQuery($name)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'createNamedQuery', array('name' => $name), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->createNamedQuery($name);
    }

    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->createNativeQuery($sql, $rsm);
    }

    public function createNamedNativeQuery($name)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->createNamedNativeQuery($name);
    }

    public function createQueryBuilder()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'createQueryBuilder', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->createQueryBuilder();
    }

    public function flush($entity = null)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'flush', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->flush($entity);
    }

    public function find($className, $id, $lockMode = null, $lockVersion = null)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'find', array('className' => $className, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->find($className, $id, $lockMode, $lockVersion);
    }

    public function getReference($entityName, $id)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getReference($entityName, $id);
    }

    public function getPartialReference($entityName, $identifier)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getPartialReference($entityName, $identifier);
    }

    public function clear($entityName = null)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'clear', array('entityName' => $entityName), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->clear($entityName);
    }

    public function close()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'close', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->close();
    }

    public function persist($entity)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'persist', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->persist($entity);
    }

    public function remove($entity)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'remove', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->remove($entity);
    }

    public function refresh($entity)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'refresh', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->refresh($entity);
    }

    public function detach($entity)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'detach', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->detach($entity);
    }

    public function merge($entity)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'merge', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->merge($entity);
    }

    public function copy($entity, $deep = false)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->copy($entity, $deep);
    }

    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->lock($entity, $lockMode, $lockVersion);
    }

    public function getRepository($entityName)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getRepository', array('entityName' => $entityName), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getRepository($entityName);
    }

    public function contains($entity)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'contains', array('entity' => $entity), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->contains($entity);
    }

    public function getEventManager()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getEventManager', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getEventManager();
    }

    public function getConfiguration()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getConfiguration', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getConfiguration();
    }

    public function isOpen()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'isOpen', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->isOpen();
    }

    public function getUnitOfWork()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getUnitOfWork', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getUnitOfWork();
    }

    public function getHydrator($hydrationMode)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getHydrator($hydrationMode);
    }

    public function newHydrator($hydrationMode)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->newHydrator($hydrationMode);
    }

    public function getProxyFactory()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getProxyFactory', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getProxyFactory();
    }

    public function initializeObject($obj)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'initializeObject', array('obj' => $obj), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->initializeObject($obj);
    }

    public function getFilters()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'getFilters', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->getFilters();
    }

    public function isFiltersStateClean()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'isFiltersStateClean', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->isFiltersStateClean();
    }

    public function hasFilters()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'hasFilters', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return $this->valueHolderfa6bc->hasFilters();
    }

    /**
     * Constructor for lazy initialization
     *
     * @param \Closure|null $initializer
     */
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;

        $reflection = $reflection ?? new \ReflectionClass(__CLASS__);
        $instance   = $reflection->newInstanceWithoutConstructor();

        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);

        $instance->initializerc716d = $initializer;

        return $instance;
    }

    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;

        if (! $this->valueHolderfa6bc) {
            $reflection = $reflection ?? new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolderfa6bc = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);

        }

        $this->valueHolderfa6bc->__construct($conn, $config, $eventManager);
    }

    public function & __get($name)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, '__get', ['name' => $name], $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        if (isset(self::$publicPropertiesd7c1a[$name])) {
            return $this->valueHolderfa6bc->$name;
        }

        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');

        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa6bc;

            $backtrace = debug_backtrace(false, 1);
            trigger_error(
                sprintf(
                    'Undefined property: %s::$%s in %s on line %s',
                    $realInstanceReflection->getName(),
                    $name,
                    $backtrace[0]['file'],
                    $backtrace[0]['line']
                ),
                \E_USER_NOTICE
            );
            return $targetObject->$name;
        }

        $targetObject = $this->valueHolderfa6bc;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();

        return $returnValue;
    }

    public function __set($name, $value)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, '__set', array('name' => $name, 'value' => $value), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');

        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa6bc;

            $targetObject->$name = $value;

            return $targetObject->$name;
        }

        $targetObject = $this->valueHolderfa6bc;
        $accessor = function & () use ($targetObject, $name, $value) {
            $targetObject->$name = $value;

            return $targetObject->$name;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();

        return $returnValue;
    }

    public function __isset($name)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, '__isset', array('name' => $name), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');

        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa6bc;

            return isset($targetObject->$name);
        }

        $targetObject = $this->valueHolderfa6bc;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();

        return $returnValue;
    }

    public function __unset($name)
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, '__unset', array('name' => $name), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');

        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa6bc;

            unset($targetObject->$name);

            return;
        }

        $targetObject = $this->valueHolderfa6bc;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);

            return;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $accessor();
    }

    public function __clone()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, '__clone', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        $this->valueHolderfa6bc = clone $this->valueHolderfa6bc;
    }

    public function __sleep()
    {
        $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, '__sleep', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;

        return array('valueHolderfa6bc');
    }

    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }

    public function setProxyInitializer(\Closure $initializer = null) : void
    {
        $this->initializerc716d = $initializer;
    }

    public function getProxyInitializer() : ?\Closure
    {
        return $this->initializerc716d;
    }

    public function initializeProxy() : bool
    {
        return $this->initializerc716d && ($this->initializerc716d->__invoke($valueHolderfa6bc, $this, 'initializeProxy', array(), $this->initializerc716d) || 1) && $this->valueHolderfa6bc = $valueHolderfa6bc;
    }

    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolderfa6bc;
    }

    public function getWrappedValueHolderValue()
    {
        return $this->valueHolderfa6bc;
    }
}

if (!\class_exists('EntityManager_9a5be93', false)) {
    \class_alias(__NAMESPACE__.'\\EntityManager_9a5be93', 'EntityManager_9a5be93', false);
}
