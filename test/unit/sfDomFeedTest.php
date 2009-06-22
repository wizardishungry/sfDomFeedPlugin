<?php

require_once(dirname(__FILE__).'/../bootstrap/unit.php');

require_once(dirname(__FILE__).'/../../lib/sfDomFeed.class.php');
require_once(dirname(__FILE__).'/../../lib/sfRssDomFeed.class.php');

$t = new lime_test(1, new lime_output_color());

$feed_params = array(
  'title' => 'foo', 
  'link' => 'bar', 
  'description' => 'foobar baz',
  'language' => 'fr', 
  'authorName' => 'francois',
  'authorEmail' => 'francois@toto.com',
  'authorLink' => 'http://francois.toto.com',
  'subtitle' => 'this is foo bar',
  'categories' => array('foo', 'bar'),
  'feedUrl' => 'http://www.example.com',
  'encoding' => 'UTF-16',
);

$feed=new sfRssDomFeed();
$t->isa_ok($feed->initialize($feed_params), 'sfRssDomFeed', 'initialize() returns the current feed object');
