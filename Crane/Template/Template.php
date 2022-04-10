<?php

namespace Crane\Template;


class Template
{

    private $file;

    public function __construct($file)
    {
        $this->file = $file;
        if (!file_exists($this->file)) {
            throw new \Error(sprintf("The file '%s' does not exist.", $this->file));
        }
    }

    public function template(array $context = [])
    {

        $contents = file_get_contents($this->file);

        $partial = TemplateUtils::includeExtended($contents);
        $partial = TemplateUtils::includeFiles($partial);
        $partial = TemplateUtils::replacePairs($partial, $context);
        $partial = TemplateUtils::replacePaths($partial, $context);
        $partial = LambdaUtils::parseFunctions($partial, $context);

        return $partial;
        /*
           * Maybe a  feature where templates can extend beyond first generation
            $extends_regex = "(?s)\{\{\s*?extends\s*?([^\s]+?)\s*?}}";
    
            return preg_match("#$extends_regex#", $r, $matches) ?
            $final_pack($r) : $r;
    
           */
    }
}
