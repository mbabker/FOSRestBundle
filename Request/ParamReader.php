<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\RestBundle\Request;

use Doctrine\Common\Annotations\Reader;
use FOS\RestBundle\Controller\Annotations\ParamInterface;

/**
 * Class loading "@ParamInterface" annotations from methods.
 *
 * @author Alexander <iam.asm89@gmail.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Boris Guéry  <guery.b@gmail.com>
 */
final class ParamReader implements ParamReaderInterface
{
    private ?\Doctrine\Common\Annotations\Reader $annotationReader;

    public function __construct(?Reader $annotationReader = null)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * {@inheritdoc}
     */
    public function read(\ReflectionClass $reflection, string $method): array
    {
        if (!$reflection->hasMethod($method)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" has no method "%s".', $reflection->getName(), $method));
        }

        $methodParams = $this->getParamsFromMethod($reflection->getMethod($method));
        $classParams = $this->getParamsFromClass($reflection);

        return array_merge($methodParams, $classParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getParamsFromMethod(\ReflectionMethod $method): array
    {
        $annotations = [];
        if (\PHP_VERSION_ID >= 80000) {
            $annotations = $this->getParamsFromAttributes($method);
        }

        if (null !== $this->annotationReader) {
            $annotations = array_merge(
                $annotations,
                $this->annotationReader->getMethodAnnotations($method) ?? []
            );
        }

        return $this->getParamsFromAnnotationArray($annotations);
    }

    /**
     * {@inheritdoc}
     */
    public function getParamsFromClass(\ReflectionClass $class): array
    {
        $annotations = [];
        if (\PHP_VERSION_ID >= 80000) {
            $annotations = $this->getParamsFromAttributes($class);
        }

        if (null !== $this->annotationReader) {
            $annotations = array_merge(
                $annotations,
                $this->annotationReader->getClassAnnotations($class) ?? []
            );
        }

        return $this->getParamsFromAnnotationArray($annotations);
    }

    /**
     * @return ParamInterface[]
     */
    private function getParamsFromAnnotationArray(array $annotations): array
    {
        $params = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ParamInterface) {
                $params[$annotation->getName()] = $annotation;
            }
        }

        return $params;
    }

    /**
     * @param \ReflectionClass|\ReflectionMethod $reflection
     *
     * @return ParamInterface[]
     */
    private function getParamsFromAttributes($reflection): array
    {
        $params = [];
        foreach ($reflection->getAttributes(ParamInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $param = $attribute->newInstance();
            $params[$param->getName()] = $param;
        }

        return $params;
    }
}
