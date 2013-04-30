<?php

namespace DTL\Ghag;

class DOMUtil
{
    public function sort($collection, $attr)
    {
        $array = $this->nodeListToArray($collection);
        usort($array, function ($a, $b) use ($attr) {
            $v1 = $a->getAttribute($attr);
            $v2 = $b->getAttribute($attr);

            if ($v1 > $v2) {
                return false;
            }

            return true;
        });

        return $array;
    }

    public function distinct($collection, $attr)
    {
        $distinct = array();
        foreach ($collection as $el) {
            $v = $el->getAttribute($attr);
            $distinct[$attr] = $el;
        }

        return array_values($distinct);
    }

    public function nodeListToArray($nodeList)
    {
        if (gettype($nodeList) == 'array') {
            return $nodeList;
        }

        $array = array();
        foreach ($nodeList as $node) {
            $array[] = $node;
        }

        return $array;
    }
}
