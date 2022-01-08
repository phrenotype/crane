<?php

namespace Crane\Template;

class LambdaUtils
{
    public static function isStaticMethod($pathConstruct)
    {
        $path = explode('.', $pathConstruct);
        $count = count($path);
        if ($count > 1) {

            if (function_exists(join('\\', $path))) {
                return false;
            }
            return true;
        }
        return false;
    }
    public static function callAsStatic($pathConstruct, $params = [])
    {
        $path = explode('.', $pathConstruct);
        $count = count($path);
        if ($count > 1) {
            $methodName = $path[$count - 1];
            unset($path[$count - 1]);
            $toCall = [join('\\', $path), $methodName];
            if (is_callable($toCall)) {
                return call_user_func_array($toCall, $params);
            }
        }
        return false;
    }
    public static function call($construct)
    {
        $splitSpaceRegex = TemplateUtils::regexNotWithinQuotes('\s+');
        $replacement = '';

        if (trim($construct) === '') {
            /* It's an empty expression */
            $replacement = '';
        } else if (preg_match("#^[\w.]+$#", $construct)) {
            /* It's a function or static method with no args */
            $replacement = str_replace('.', '\\', $construct);
            if (is_callable($replacement)) {
                $replacement = $replacement();
            } else if (LambdaUtils::isStaticMethod($construct)) {
                /* Try to call it as a static method */
                $replacement = LambdaUtils::callAsStatic($construct);
            } else {
                $replacement = '';
            }
        } else {
            $pieces = preg_split($splitSpaceRegex, $construct);
            $functionName = $pieces[0];
            $newPieces = array_slice($pieces, 1);

            /* Rid the pieces of ' or " */
            for ($u = 0; $u < count($newPieces); $u++) {
                $p = &$newPieces[$u];
                $p = trim($p, '"');
                $p = trim($p, "'");
            }

            if (!LambdaUtils::isStaticMethod($functionName)) {
                $functionNameProper = str_replace('.', '\\', $functionName);
                $rf = new \ReflectionFunction($functionNameProper);
                $numberOfParameters = $rf->getNumberOfParameters();
                if ($numberOfParameters === 1) {

                    /* 
                        This is a really cool attempt at detecting currying vs single param function call
                        Here, the difference will not matter because even if it is called curry style,
                        Since the parameter expected is only one, it will never matter.
                        Because the expected number of paramters is just one, in newPieces.                
                    */
                    $replacement = $functionNameProper;
                    foreach ($newPieces as $piece) {
                        $replacement = $replacement($piece);
                    }
                } else {

                    $replacement = call_user_func_array($functionNameProper, $newPieces);
                }
            } else if (LambdaUtils::isStaticMethod($functionName)) {

                $parts  = explode('.', $functionName);
                $count = count($parts);

                $className = join('\\', array_slice($parts, 0, $count - 1));
                $methodName = $parts[$count - 1];

                $rf = new \ReflectionMethod($className, $methodName);
                $numberOfParameters = $rf->getNumberOfParameters();

                if ($numberOfParameters === 1) {

                    /* 
                        This is a really cool attempt at detecting currying vs single param function call
                        Here, the difference will not matter because even if it is called curry style,
                        Since the parameter expected is only one, it will never matter.
                        Because the expected number of paramters is just one, in newPieces.                
                    */
                    $replacement = LambdaUtils::callAsStatic($functionName, [$newPieces[0]]);
                    unset($newPieces[0]);
                    foreach ($newPieces as $piece) {
                        $replacement = $replacement($piece);
                    }
                } else {

                    $replacement = LambdaUtils::callAsStatic($functionName, $newPieces);
                }
            }
        }

        return $replacement;
    }

    public static function parseFunctions(string $content, array $context)
    {

        $constructRegex = "(?s)\{%=\s*(.*?)\s*%}";

        $blockRegex = function ($block) {
            return "(?s)\{%=\s*$block\s*%\}";
        };

        preg_match_all("#$constructRegex#", $content, $matches);
        $constructs = $matches[1] ?? [];

        $finalContent = $content;

        foreach ($constructs as $construct) {

            $replacement = self::call($construct);

            $regex = $blockRegex($construct);

            $finalContent = preg_replace(
                "#$regex#",
                $replacement,
                $finalContent
            );
        }

        return $finalContent;
    }
}
