<?php
//C:\AppServ\www\cca\application\modules\campaign\Bootstrap.php
class Power_Bootstrap extends Zend_Application_Module_Bootstrap{ 

	protected function _initDoctype() { 
	define("STYLE", "ui-state-default");
	define("HIGHLIGHT_STYLE", "ui-state-highlight");
	define("ACTIVE_STYLE", "ui-state-active");
	define("FOCUS_STYLE", "ui-state-focus");
	define("HOVER_STYLE", "ui-state-hover");
	define("ERROR_STYLE", "ui-state-error");
	}
}
