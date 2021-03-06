<?php

class GettextParserAdapter_Smarty extends GettextParserAdapter
{
    public function __construct()
    {
        //add patterns

        // search for smarty modifier: {"Text to be localized"|_}
        $this->patterns[] = new GettextParserPattern("~\{\"([^\"]+)\"\|_([^\}]*)\}~");

        //search for block.t: {t}Text to be localized{/t}
        $this->patterns[] = new GettextParserPattern("~\{t\}([^\{]+)\{/t\}~");

        // search for gettext function call: {_("Text to be localized")}
        $this->patterns[] = new GettextParserPattern("~_\((?:\"|')([^\)]+)(?:\"|')\)~");
        $this->patterns[] = new GettextParserPattern("~__\((?:\"|')([^\)]+)(?:\"|')\)~");
    }
}