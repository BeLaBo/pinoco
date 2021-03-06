<?php
function setupTALRenderer($renderer) {
    if (!class_exists('PHPTAL')) {
        require_once dirname(__FILE__) . '/PHPTAL.php';
    }
    $renderer->cfg->phpCodeDestination = Pinoco::instance()->sysdir . "/cache";
    $renderer->cfg->encoding = "UTF-8";
    $renderer->cfg->outputMode = PHPTAL::XHTML;  // XHTML, XML or HTML5
}
