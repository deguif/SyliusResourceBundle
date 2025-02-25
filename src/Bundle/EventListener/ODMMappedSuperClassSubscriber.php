<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\ResourceBundle\EventListener;

use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

@trigger_error(sprintf('The "%s" class is deprecated since Sylius 1.3. Doctrine MongoDB and PHPCR support will no longer be supported in Sylius 2.0.', ODMMappedSuperClassSubscriber::class), \E_USER_DEPRECATED);

/**
 * Doctrine listener used to manipulate mappings.
 */
final class ODMMappedSuperClassSubscriber extends AbstractDoctrineSubscriber
{
    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();

        $this->convertToDocumentIfNeeded($metadata);

        if (!$metadata->isMappedSuperclass) {
            $this->setAssociationMappings($metadata, $eventArgs->getDocumentManager()->getConfiguration());
        } else {
            $this->unsetAssociationMappings($metadata);
        }
    }

    private function convertToDocumentIfNeeded(ClassMetadataInfo $metadata)
    {
        if (false === $metadata->isMappedSuperclass) {
            return;
        }

        try {
            $resourceMetadata = $this->resourceRegistry->getByClass($metadata->getName());
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        if ($metadata->getName() === $resourceMetadata->getClass('model')) {
            $metadata->isMappedSuperclass = false;
        }
    }

    /**
     * @param $configuration
     */
    private function setAssociationMappings(ClassMetadataInfo $metadata, $configuration)
    {
        foreach (class_parents($metadata->getName()) as $parent) {
            if (false === in_array($parent, $configuration->getMetadataDriverImpl()->getAllClassNames())) {
                continue;
            }

            $parentMetadata = new ClassMetadata(
                $parent,
                $configuration->getNamingStrategy(),
            );

            // Wakeup Reflection
            $parentMetadata->wakeupReflection($this->getReflectionService());

            // Load Metadata
            $configuration->getMetadataDriverImpl()->loadMetadataForClass($parent, $parentMetadata);

            if (false === $this->isResource($parentMetadata)) {
                continue;
            }

            if ($parentMetadata->isMappedSuperclass) {
                foreach ($parentMetadata->associationMappings as $key => $value) {
                    if ($this->isRelation($value['association']) && !isset($metadata->associationMappings[$key])) {
                        $metadata->associationMappings[$key] = $value;
                    }
                }
            }
        }
    }

    private function unsetAssociationMappings(ClassMetadataInfo $metadata)
    {
        if (false === $this->isResource($metadata)) {
            return;
        }

        foreach ($metadata->associationMappings as $key => $value) {
            if ($this->isRelation($value['association'])) {
                unset($metadata->associationMappings[$key]);
            }
        }
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isRelation($type)
    {
        return in_array(
            $type,
            [
                ClassMetadataInfo::REFERENCE_ONE,
                ClassMetadataInfo::REFERENCE_MANY,
                ClassMetadataInfo::EMBED_ONE,
                ClassMetadataInfo::EMBED_MANY,
            ],
            true,
        );
    }
}
