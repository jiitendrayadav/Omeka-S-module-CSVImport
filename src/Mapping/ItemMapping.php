<?php
namespace CSVImport\Mapping;


class ItemMapping
{
    
    protected $args;
    
    protected $api;
    
    protected $logger;
    
    public function __construct($args, $api, $logger)
    {
        $this->args = $args;
        $this->api = $api;
        $this->logger = $logger;
    }
    
    public static function getLabel()
    {
        return "Item Data";
    }
    
    public static function getName()
    {
        return 'item';
    }
    
    public static function getSidebar($view)
    {
        return "
        <div id='item-sidebar' class='sidebar flags'>
            <p>Data from this column applies to the entire item being imported.</p>
            <p>Settings here will override any corresponding settings in Basic Import Settings.</p>
            <ul>
                <li data-flag='column-itemset-id'>
                    <a href='#' class='button'><span>Item Set ID</span></a>
                </li>
                <li data-flag='column-resourcetemplate'>
                    <a href='#' class='button'><span>Resource Template Name</span></a>
                </li>
                <li data-flag='column-resourceclass'>
                    <a href='#' class='button'><span>Resource Class Term</span></a>
                </li>
                <li data-flag='column-owneremail'>
                    <a href='#' class='button'><span>Owner Email Address</span></a>
                </li>
            </ul>
        </div>
        ";
    }
    
    public function processRow($row)
    {
        $itemJson = [];

            
        //first, pull in the global settings
        if (isset($this->args['o:item_set'])) {
            $itemSets = $this->args['o:item_set'];
            $itemJson['o:item_set'] = [];
            foreach($itemSets as $itemSetId) {
                $itemJson['o:item_set'][] = array('o:id' => $itemSetId);
            }
        }
        if (isset($this->args['o:resource_class'])) {
            $resourceClass = $this->args['o:resource_class']['o:id'];
            $itemJson['o:resource_class'] = ['o:id' => $resourceClass];
        }
        if (isset($this->args['o:resource_template'])) {
            $resourceTemplate = $this->args['o:resource_template']['o:id'];
            $itemJson['o:resource_template'] = ['o:id' => $resourceTemplate];
        }
        
        $multivalueSeparator = $this->args['multivalue-separator'];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $itemSetMap = isset($this->args['column-itemset-id']) ? array_keys($this->args['column-itemset-id']) : [];
        
        $resourceTemplateMap = isset($this->args['column-resourcetemplate']) ? array_keys($this->args['column-resourcetemplate']) : [];
        $resourceClassMap = isset($this->args['column-resourceclass']) ? array_keys($this->args['column-resourceclass']) : [];
        $userMap = isset($this->args['column-useremail']) ? array_keys($this->args['column-useremail']) : [];

        
        foreach($row as $index => $values) {
            //maybe weird, but just assuming a split for ids for simplicity's sake
            //since a list of ids shouldn't have any weird separators
            $values = explode($multivalueSeparator, $values);
            if (in_array($index, $itemSetMap)) {
                foreach($values as $itemSetId) {
                    $itemJson['o:item_set'][] = array('o:id' => trim($itemSetId));
                }
            }
            if (in_array($index, $resourceTemplateMap)) {
                $template = $this->findResourceTemplate($values);
            }
            if (in_array($index, $resourceClassMap)) {
                $template = $this->findResourceClass($values);
            }
            if (in_array($index, $userMap)) {
                $template = $this->findUser($values);
            }
        }
        return $itemJson;
    }
    
    protected function findResourceClass($term)
    {
        $response = $this->api->read('resource_class', array('term' => $term));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
    
    protected function findResourceTemplate($label)
    {
        $response = $this->api->read('resource_template', array('label' => $label));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
    
    protected function findUser($email)
    {
        $response = $this->api->read('user', array('email' => $email));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
}