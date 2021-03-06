<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\ImportForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ImportForm(null, $options);
        $config = $services->get('Config');
        $form->setConfigCsvImport($config['csvimport']);
        $form->setEventManager($services->get('EventManager'));
        return $form;
    }
}
