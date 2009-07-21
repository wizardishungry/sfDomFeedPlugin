<?php

/*
 * This file is part of the sfDomFeedPlugin package.
 * (c) 2009 Jon Williams <jwilliams@limewire.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfDomFeed.
 *
 * @package    sfDomFeed
 * @author     Jon Williams <jwilliams@limewire.com>
 */
abstract class sfDomFeed extends sfDomStorage
{

    protected $dom; // DOMDocument
    protected $plugin_path;
    protected $items=Array();
    protected $family; // e.g. RSS or Atom
    protected $xpath_item; // XPath expression for feed item
    protected $xpath_channel; // for the root channel
    protected $decorate_rules = Array( // feed is global; item is foreach item
        'feed'=>Array(),'item'=>Array()); // xpath query=>transform (string or array-callback)

    public function __construct($feed_array=array(),$version='1.0',$encoding='UTF-8')
    {
        parent::__construct(); /// we call initialize() later so we don't need to pass feed_array in yet
        $dom=$this->dom=new DOMDocument($version,$encoding);
        $this->context=sfContext::getInstance();
        $this->plugin_path=realpath(dirname(__FILE__).'/../');

        if($feed_array)
        {
            $this->initialize($feed_array);
        }

        if(! $dom->load($this->genTemplatePath(),LIBXML_NOERROR))
            throw new sfDomFeedException("DOMDocument::load failed");

        $this->setEncoding($encoding); // todo encoding should be moved into a series of hash style rules

        // prepend the xpath item expression to each of the item decorate rules
        // fixme should this be done at rule parsetime?
        foreach($this->decorate_rules['item'] as $xpath => $rule)
        {
            $decorate_rules_item[$this->xpath_item.$xpath]=$rule;
        }
        $this->decorate_rules['item']=$decorate_rules_item;

    }

    public function initialize($data_array)
    {
        // special cases -- should be refactored to elsewhere?
        if(array_key_exists('feed_items',$data_array))
        {
            $this->items=$data_array['feed_items'];
            unset($data_array['feed_items']);
        }
        return parent::initialize($data_array);
    }

    // simple methods to preserve compat with sfFeed2Plugin

    public function asXml()
    {
        // I suppose presuming that we're emiting XML is a *little presumputous*

        // the following probably should be refactored
        $this->context->getResponse()->setContentType('application/'.$this->family.'+xml; charset='.$this->getEncoding());
        $dom=$this->dom->cloneNode(TRUE); // may be expensive to do a deep clone
        return $this->decorateDom($dom)->saveXML();
    }

    /**
    * Retrieves the feed items.
    *
    * @return array an array of sfDomFeedItem objects
    */
    public function getItems()
    {
        return $this->items;
    }
 
    /**
    * Defines the items of the feed.
    *
    * Caution: in previous versions, this method used to accept all kinds of objects.
    * Now only objects of class sfDomFeedItem are allowed.
    *
    * @param array an array of sfDomFeedItem objects
    *
    * @return sfFeed the current sfFeed object
    */
    public function setItems($items = array())
    {
        $this->items = array();
        $this->addItems($items);
    
        return $this;
    }
 
    /**
    * Adds one item to the feed.
    *
    * @param sfDomFeedItem an item object
    *
    * @return sfFeed the current sfFeed object
    */
    public function addItem($item)
    {
        if (!($item instanceof sfDomFeedItem))
        {
        // the object is of the wrong class
        $error = 'Parameter of addItem() is not of class sfDomFeedItem';
    
        throw new sfDomFeedException($error);
        }
        //$item->setFeed($this);
        // not sure we need this 
        $this->items[] = $item;
    
        return $this;
    }
    
    /**
    * Adds several items to the feed.
    *
    * @param array an array of sfDomFeedItem objects
    *
    * @return sfFeed the current sfFeed object
    */
    public function addItems($items)
    {
        if(is_array($items))
        {
        foreach($items as $item)
        {
            $this->addItem($item);
        }
        }
    
        return $this;
    }


    public function fromXml($string)
    {
        throw new sfDomFeedException('Not implemented');
    }

    // protected methods
    
    protected function genTemplatePath()
    {
        return $this->plugin_path."/data/templates/".$this->family.'.xml'; // todo make name more canonical with a prefix "root-rss"
    }

    protected function decorateDom(DOMDocument $dom)
    {
        $dom->encoding=$this->getEncoding();
        $xp=new DOMXPath($dom);
        $channel = $xp->query($this->xpath_channel);
        $channel = $channel->item(0);
        $this->decorate($this,$channel,$this->decorate_rules['feed']);

        $item_nodes = $xp->query($this->xpath_item);

        if(count($item_nodes)!=1)
            throw new sfDomFeedException('XPath query of '.$this->family.
                ' template for feed item got an unexpected (!1) number of feed items: '.count($items));

        $template_item_node=$item_nodes->item(0);
        $items_parent=$template_item_node->parentNode;
        $items_parent->removeChild($template_item_node);
        $items=Array(); // holds dom nodes until they can be readded (simplifies xpath expressions)

        foreach($this->items as $feed_item)
        {
            $node = $template_item_node->cloneNode(TRUE);
            $items_parent->appendChild($node);
            $feed_item->decorate($this,$node,$this->decorate_rules['item']);  // todo: parsing this once per item is SLOW 
            $items_parent->removeChild($node); // so the xpath expressions for template items work identically in this context
            $items[]=$node; // we could do some kind of sort key here todo
        }
        foreach($items as $node)
            $items_parent->appendChild($node); // readd them to the dom
        

        return $dom;
    }

    public function setEncoding($encoding)
    {
        // done to synchornize with wrapped DOMDocument
        $this->dom->encoding=$encoding;
        parent::setEncoding($encoding);
    }
    public function genUrl(DOMElement $url)
    {
      return sfContext::getInstance()->getController()->genUrl($url->textContent,true);
    }
}
