<?php
namespace CSVImport\Controller;

use CSVImport\Form\ImportForm;
use CSVImport\Form\MappingForm;
use CSVImport\CsvFile;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $form = new ImportForm($this->getServiceLocator());
        $view->form = $form;
        return $view;
    }

    public function mapAction()
    {
        $view = new ViewModel;
        $form = new MappingForm($this->getServiceLocator());
        $view->setVariable('form', $form);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles()->toArray();
            if (empty($files)) {
                $post = $this->params()->fromPost();
                $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                $job = $dispatcher->dispatch('CSVImport\Job\Import', $post);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
            } else {
                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );

                $tmpFile = $post['csv']['tmp_name'];
                $csvFile = new CsvFile($this->getServiceLocator());
                $csvPath = $csvFile->getTempPath();
                $csvFile->moveToTemp($tmpFile);
                $csvFile->loadFromTempPath();
                $columns = $csvFile->getHeaders();
                $view->setVariable('mediaForms', $this->getMediaForms());

                $config = $this->serviceLocator->get('Config');
                $autoMaps = $this->getAutomaps($columns);
                $view->setVariable('automaps', $autoMaps);
                $view->setVariable('mappings', $config['csv_import_mappings']);
                $view->setVariable('columns', $columns);
                $view->setVariable('csvpath', $csvPath);
            }
        }
        return $view;
    }

    public function pastImportsAction()
    {
        $view = new ViewModel;
        return $view;
    }

    protected function getMediaForms()
    {
        $services = $this->getServiceLocator();
        $mediaIngester = $services->get('Omeka\MediaIngesterManager');

        $forms = [];
        foreach ($mediaIngester->getRegisteredNames() as $ingester) {
            $forms[$ingester] = [
                'label' => $mediaIngester->get($ingester)->getLabel(),
            ];
        }
        return $forms;
    }

    protected function getAutomaps($columns)
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $autoMaps = [];
        foreach($columns as $index=>$column) {
            if (strpos($column, ':') !== false) {
                $response = $api->search('properties', array('term' => trim($column)));
                $content = $response->getContent();
                if (! empty($content)) {
                    $property = $content[0];
                    $autoMaps[$index] = $property;
                }
            }
        }
        return $autoMaps;
    }
}
