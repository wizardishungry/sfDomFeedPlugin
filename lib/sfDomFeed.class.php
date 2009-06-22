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
abstract class sfDomFeed extends DOMDocument
{
    abstract public function getFamily();
    protected $plugin_path;

    public function __construct($feed_array=null,$version='1.0',$encoding='UTF-8')
    {
        parent::__construct($version,$encoding);
        $this->context=sfContext::getInstance();
        $this->plugin_path=realpath(dirname(__FILE__).'/../');

        if($feed_array)
        {
            $this->initialize($feed_array);
        }
        if(! $this->load($this->getFamilyTemplatePath(),LIBXML_NOERROR))
            throw new sfDomFeedException("DOMDocument::load failed");
    }

    public function initialize($feed_array)
    {
        return $this;
    }
    
    protected function getFamilyTemplatePath()
    {
        return $this->plugin_path."/data/templates/".$this->getFamily().'.xml'; // todo make name more canonical with a prefix "root-rss"
    }
}
