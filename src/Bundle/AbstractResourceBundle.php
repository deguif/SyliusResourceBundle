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

namespace Sylius\Bundle\ResourceBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Doctrine\Bundle\PHPCRBundle\DependencyInjection\Compiler\DoctrinePhpcrMappingsPass;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Driver\Exception\UnknownDriverException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class AbstractResourceBundle extends Bundle implements ResourceBundleInterface
{
    /**
     * Configure format of mapping files.
     */
    protected string $mappingFormat = ResourceBundleInterface::MAPPING_XML;

    public function build(ContainerBuilder $container): void
    {
        if (null !== $this->getModelNamespace()) {
            foreach ($this->getSupportedDrivers() as $driver) {
                [$compilerPassClassName, $compilerPassMethod] = $this->getMappingCompilerPassInfo($driver);

                if (class_exists($compilerPassClassName)) {
                    if (!method_exists($compilerPassClassName, $compilerPassMethod)) {
                        throw new InvalidConfigurationException(
                            "The 'mappingFormat' value is invalid, must be 'xml', 'yaml' or 'annotation'.",
                        );
                    }

                    switch ($this->mappingFormat) {
                        case ResourceBundleInterface::MAPPING_XML:
                        case ResourceBundleInterface::MAPPING_YAML:
                            $container->addCompilerPass($compilerPassClassName::$compilerPassMethod(
                                [$this->getConfigFilesPath() => $this->getModelNamespace()],
                                [$this->getObjectManagerParameter()],
                                sprintf('%s.driver.%s', $this->getBundlePrefix(), $driver),
                            ));

                            break;
                        case ResourceBundleInterface::MAPPING_ANNOTATION:
                            $container->addCompilerPass($compilerPassClassName::$compilerPassMethod(
                                [$this->getModelNamespace()],
                                [$this->getConfigFilesPath()],
                                [sprintf('%s.object_manager', $this->getBundlePrefix())],
                                sprintf('%s.driver.%s', $this->getBundlePrefix(), $driver),
                            ));

                            break;
                    }
                }
            }
        }
    }

    /**
     * Return the prefix of the bundle.
     */
    protected function getBundlePrefix(): string
    {
        return Container::underscore(substr((string) strrchr(static::class, '\\'), 1, -6));
    }

    /**
     * Return the directory where are stored the doctrine mapping.
     */
    protected function getDoctrineMappingDirectory(): string
    {
        return 'model';
    }

    /**
     * Return the entity namespace.
     *
     * @return string
     */
    protected function getModelNamespace(): ?string
    {
        return (new \ReflectionClass($this))->getNamespaceName() . '\\Model';
    }

    /**
     * Return mapping compiler pass class depending on driver.
     *
     *
     *
     * @throws UnknownDriverException
     */
    protected function getMappingCompilerPassInfo(string $driverType): array
    {
        switch ($driverType) {
            case SyliusResourceBundle::DRIVER_DOCTRINE_MONGODB_ODM:
                @trigger_error(sprintf(
                    'The "%s" driver is deprecated in Sylius 1.3. Doctrine MongoDB and PHPCR will no longer be supported in Sylius 2.0.',
                    $driverType,
                ), \E_USER_DEPRECATED);

                $mappingsPassClassname = DoctrineMongoDBMappingsPass::class;

                break;
            case SyliusResourceBundle::DRIVER_DOCTRINE_ORM:
                $mappingsPassClassname = DoctrineOrmMappingsPass::class;

                break;
            case SyliusResourceBundle::DRIVER_DOCTRINE_PHPCR_ODM:
                @trigger_error(sprintf(
                    'The "%s" driver is deprecated in Sylius 1.3. Doctrine MongoDB and PHPCR will no longer be supported in Sylius 2.0.',
                    $driverType,
                ), \E_USER_DEPRECATED);

                $mappingsPassClassname = DoctrinePhpcrMappingsPass::class;

                break;
            default:
                throw new UnknownDriverException($driverType);
        }

        $compilerPassMethod = sprintf('create%sMappingDriver', ucfirst($this->mappingFormat));

        return [$mappingsPassClassname, $compilerPassMethod];
    }

    /**
     * Return the absolute path where are stored the doctrine mapping.
     */
    protected function getConfigFilesPath(): string
    {
        return sprintf(
            '%s/Resources/config/doctrine/%s',
            $this->getPath(),
            strtolower($this->getDoctrineMappingDirectory()),
        );
    }

    protected function getObjectManagerParameter(): string
    {
        return sprintf('%s.object_manager', $this->getBundlePrefix());
    }
}
