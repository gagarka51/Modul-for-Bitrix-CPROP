<?php 

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class shark_cprop extends CModule
{
	
	function __construct()
	{
		if (file_exists(__DIR__ . "/version.php")) {
			$arModuleVersion = array();

			include_once(__DIR__ . "/version.php");

			$this->MODULE_ID = str_replace("_", ".", get_class($this));
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
			$this->MODULE_NAME = Loc::getMessage("SHARK_CPROP_MODULE_NAME");
			$this->MODULE_DESCRIPTION = Loc::getMessage("SHARK_CPROP_MODULE_DESC");
			$this->PARTNER_NAME = Loc::getMessage("SHARK_CPROP_PARTNER_NAME");
			$this->PARTNER_URI = Loc::getMessage("SHARK_CPROP_PARTNER_URI");
		}
		return false;
	}

	function isVersionD7()
    {
        return true;
    }

	public function DoInstall()
	{
		global $APPLICATION;

		if ($this->isVersionD7()) {
			$this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();

            ModuleManager::registerModule($this->MODULE_ID);
		} else {
			$APPLICATION->ThrowException(Loc::getMessage("SHARK_CPROP_INSTALL_ERROR_VERSION"));
		}
	}

	public function DOUninstall()
	{
		ModuleManager::unRegisterModule($this->MODULE_ID);

		$this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();
	}

	public function InstallDB()
	{
		return true;
	}

	public function UnInstallDB()
	{
		return true;
	}

	public function InstallFiles()
	{
		return true;
	}

	public function UnInstallFiles()
	{
		return true;
	}

	public function getEvents()
	{
		return [
			[
				"FROM_MODULE" => "iblock",
				"EVENT" => "OnIblockPropertyBuildList",
				"TO_METHOD" => "GetUserTypeDescription"
			],
			[
				"FROM_MODULE" => "main",
				"EVENT" => "OnUserTypeBuildList",
				"TO_METHOD" => "GetUserTypeDescription"
			]
		];
	}

	public function InstallEvents()
	{
		$classHandler = "CIBlockPropertyCProp";
		$classHandlerUT = "CUserTypeCProp";
		$arEvents = $this->getEvents();

		foreach ($arEvents as $arEvent) {
			if ($arEvent["FROM_MODULE"] == "iblock") {
				EventManager::getInstance()->registerEventHandler(
					$arEvent["FROM_MODULE"],
					$arEvent["EVENT"],
					$this->MODULE_ID,
					$classHandler,
					$arEvent["TO_METHOD"]
				);
			}
			
			if ($arEvent["FROM_MODULE"] == "main") {
				EventManager::getInstance()->registerEventHandler(
					$arEvent["FROM_MODULE"],
					$arEvent["EVENT"],
					$this->MODULE_ID,
					$classHandlerUT,
					$arEvent["TO_METHOD"]
				);
			}
		}

		return true;
	}

	public function UninstallEvents()
	{
		$classHandler = "CIBlockPropertyCProp";
		$classHandlerUT = "CUserTypeCProp";
		$arEvents = $this->getEvents();

		foreach ($arEvents as $arEvent) {
			if ($arEvent["FROM_MODULE"] == "iblock") {
				EventManager::getInstance()->registerEventHandler(
					$arEvent["FROM_MODULE"],
					$arEvent["EVENT"],
					$this->MODULE_ID,
					$classHandler,
					$arEvent["TO_METHOD"]
				);
			}
			if ($arEvent["FROM_MODULE"] == "main") {
				EventManager::getInstance()->registerEventHandler(
					$arEvent["FROM_MODULE"],
					$arEvent["EVENT"],
					$this->MODULE_ID,
					$classHandlerUT,
					$arEvent["TO_METHOD"]
				);
			}
		}

		return true;
	}
}