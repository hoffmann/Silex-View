<?php


namespace SilexView;


class TemplateView extends BaseView
{

    /*
     * Get the Template Name for the view
     * default implementation is to use the class name without namespace
     */
    function getTemplateName(){
        $cls = explode('\\', get_class($this));
        return end($cls).'.twig';

    }

    function get($request, $app)
    {
        return $app["twig"]->render($this->getTemplateName(), $this->getContextData($request, $app));
    }

    function getContextData($request, $app)
    {
    }
}