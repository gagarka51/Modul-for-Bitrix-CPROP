<?php 

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Types\StringType;

class CUserTypeCProp extends StringType
{
    public const
        USER_TYPE_ID = "complexCProp";

	private static $showedCss = false;
    private static $showedJs = false;

	public static function GetUserTypeDescription(): array
	{
		return [
            "USER_TYPE_ID" => self::USER_TYPE_ID,
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => Loc::getMessage("SHARK_CPROP_DESC"),
			"BASE_TYPE" => "string"
		];
	}

	public static function GetDBColumnType(): string
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "text";
			case "oracle":
				return "varchar2(2000 char)";
			case "mssql":
				return "varchar(2000)";
		}
	}

    public static function prepareSettings(array $arUserField): array
    {
        $arResult = [];
        
        foreach ($arUserField["SETTINGS"] as $key => $value){
            if(strstr($key, "_TITLE") !== false) {
                $code = str_replace("_TITLE", "", $key);
                $arResult[$code]["TITLE"] = $value;
            }
            else if(strstr($key, "_SORT") !== false) {
                $code = str_replace("_SORT", "", $key);
                $arResult[$code]["SORT"] = $value;
            }
            else if(strstr($key, "_TYPE") !== false) {
                $code = str_replace("_TYPE", "", $key);
                $arResult[$code]["TYPE"] = $value;
            }
        }
        
        return $arResult;
    }

	public static function GetSettingsHtml($userField, ?array $arHtmlControl, $varsFromForm): string
	{
		$btnAdd = Loc::getMessage('SHARK_CPROP_SETTING_BTN_ADD');
		$result .= '<tr><td colspan="2" align="center">
            <table id="many-fields-table" class="many-fields-table internal">        
                <tr valign="top" class="heading mf-setting-title">
                   <td>XML_ID</td>
                   <td>'.Loc::getMessage('SHARK_CPROP_SETTING_FIELD_TITLE').'</td>
                   <td>'.Loc::getMessage('SHARK_CPROP_SETTING_FIELD_SORT').'</td>
                   <td>'.Loc::getMessage('SHARK_CPROP_SETTING_FIELD_TYPE').'</td>
                </tr>';
        $result .= '<p style="color: green;"><b>Чтобы данное свойство работало корректно, используйте его как "Множественное"!</b></p>';

        self::showJsForSetting($arHtmlControl["NAME"]);
        self::showCssForSetting();

        $arSetting = $userField["SETTINGS"];

        if(!empty($arSetting)){
            foreach ($arSetting as $code => $arItem) {
                $result .= '
                       <tr valign="top">
                           <td><input type="text" class="inp-code" size="20" value="'.$code.'"></td>
                           <td><input type="text" class="inp-title" size="35" name="'.$arHtmlControl["NAME"].'['.$code.'_TITLE]" value="'.$arItem['TITLE'].'"></td>
                           <td><input type="text" class="inp-sort" size="5" name="'.$arHtmlControl["NAME"].'['.$code.'_SORT]" value="'.$arItem['SORT'].'"></td>
                           <td>
                                <select class="inp-type" name="'.$arHtmlControl["NAME"].'['.$code.'_TYPE]">
                                    '.self::getOptionList($arItem['TYPE']).'
                                </select>                        
                           </td>
                       </tr>';
            }
        }
        
        $result .= '
               <tr valign="top">
                    <td><input type="text" class="inp-code" size="20" name="" value=""></td>
                    <td><input type="text" class="inp-title" size="35" name="'.$arHtmlControl["NAME"].'["TITLE"]'.'" value=""></td>
                    <td><input type="text" class="inp-sort" size="5" value="500" name="'.$arHtmlControl["NAME"].'["SORT"]'.'" value="500"></td>
                    <td>
                        <select class="inp-type"> '.self::getOptionList().'</select>                        
                    </td>
               </tr>
             </table>   
                
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <input type="button" value="'.$btnAdd.'" onclick="addNewRows()">
                    </td>
                </tr>
                </td></tr>';

 		return $result;    
	}

    public static function CheckFields(array $userField, $value): array
    {
        $aMsg = [];
        return $aMsg;
    }

    /**
     * Функция работает для Multy
     */
    private static function getElements($sFieldName, $arValue)
    {
        $arResult = [];
        
        foreach ($sFieldName as $code => $val) {  
            if ($val["TYPE"] === "file") {
                $arResult[$code] = [];
            } else {
               $arResult[$code] = ""; 
            }
            foreach ($arValue as $key => $value) {
                if ($code === $value) {
                    $arResult[$code] = $arValue[$key+1];
                }
            }
        }

        foreach ($sFieldName as $code => $val) {
            foreach ($arResult as $key => $value) {
                if ($value == $code) {
                    $value = "";
                }
                $arResult[$key] = $value;
            }
        }

        return $arResult;
    }

    public static function OnBeforeSave($arUserField, $value)
    {
        $keyFile = "";
        $value = "";
            
        foreach ($arUserField["SETTINGS"] as $k => $v) {
            if ($v["TYPE"] === "file") {
                $keyFile = $k;
            }
        }
        
        if ($_FILES[$keyFile]) {
            if ($_FILES[$keyFile]["name"] !== "") {
                $arFile = $_FILES[$keyFile];
                $arFile["MODULE_ID"] = "shark.cprop";
                $arFile["del"] = "";

                $res = CFile::GetList(array("ID" => "ASC"), array("ORIGINAL_NAME"=>$arFile["name"], "MODULE_ID" => $arFile["MODULE_ID"]));
                while ($r = $res->GetNext())
                    $res_arr[] = $r;

                if (empty($res_arr)) {
                    CFile::SaveFile($arFile, $arFile["MODULE_ID"]);
                    $_SESSION['file_orig_name'] = $arFile["name"];
                }
            }
        }

        if($arUserField['MULTIPLE'] == 'Y') {
            if (isset($_POST[$arUserField["FIELD_NAME"]])) {
                $arUFData = $_POST[$arUserField["FIELD_NAME"]];
                $idFile = self::getFileID();
                foreach($arUFData as $key => $val) {
                    if ($val === "del") {
                        CFile::Delete($idFile);
                        unset($_POST[$arUserField["FIELD_NAME"]][$key]);
                    }
                    $value = $val;
                    unset($_POST[$arUserField["FIELD_NAME"]][$key]);
                    break; 
                }
            }
        }

        return $value;
    }

    /*
     * Должно быть, даже если свойство Multy (множественное)
     */
    public static function GetEditFormHTML(array $userField, ?array $arHtmlControl): string
    {
        $result = "";

        self::showCss();
        self::showJs();

        if(!empty($userField['SETTINGS'])){
            $arFields = $userField['SETTINGS'];
        }
        else{
            return '<span>'.Loc::getMessage('SHARK_CPROP_ERROR_INCORRECT_SETTINGS').'</span>';
        }
        $arElems = self::getElements($arFields, $arHtmlControl["VALUE"]);
        $result .= '<table class="mf-fields-list active">';

        foreach ($arFields as $code => $arItem) {
            if($arItem['TYPE'] === 'string'){
                $result .= self::showString($code, $arItem['TITLE'], $value, $arHtmlControl);
            }
            else if($arItem['TYPE'] === 'file'){
                $result .= self::showFile($code, $arItem['TITLE'], $value, $arHtmlControl);
            }
            else if($arItem['TYPE'] === 'text'){
                $result .= self::showTextarea($code, $arItem['TITLE'], $value, $arHtmlControl);
            }
            else if($arItem['TYPE'] === 'date'){
                $result .= self::showDate($code, $arItem['TITLE'], $value, $arHtmlControl);
            }
            else if($arItem['TYPE'] === 'element'){
                $result .= self::showBindElement($code, $arItem['TITLE'], $value, $arHtmlControl);
            }
            else if($arItem['TYPE'] === 'htmleditor'){
                $result .= self::showHtmlEditor($code, $arItem['TITLE'], $value, $arHtmlControl);
            }
        }
        
        $result .= '</table>';
        
        return $result;
    }

	public static function GetEditFormHTMLMulty(array $userField, ?array $arHtmlControl): string
	{
		$result = "";

        self::showCss();
        self::showJs();

        if(!empty($userField['SETTINGS'])) {
            $arFields = $userField['SETTINGS'];
        }
        else {
            return '<span>'.Loc::getMessage('SHARK_CPROP_ERROR_INCORRECT_SETTINGS').'</span>';
        }

        $result .= '<table class="mf-fields-list active">';
        $arElems = self::getElements($arFields, $arHtmlControl["VALUE"]);

        foreach ($arFields as $code => $arItem) {
            foreach ($arElems as $key => $value) {
                if($arItem['TYPE'] === 'string' && $key === $code){
                    $result .= self::showString($code, $arItem['TITLE'], $value, $arHtmlControl);
                }
                else if($arItem['TYPE'] === 'file' && $key === $code){
                    $result .= self::showFile($code, $arItem['TITLE'], $value, $arHtmlControl);
                }
                else if($arItem['TYPE'] === 'text' && $key === $code){
                    $result .= self::showTextarea($code, $arItem['TITLE'], $value, $arHtmlControl);
                }
                else if($arItem['TYPE'] === 'date' && $key === $code){
                    $result .= self::showDate($code, $arItem['TITLE'], $value, $arHtmlControl);
                }
                else if($arItem['TYPE'] === 'element' && $key === $code){
                    $result .= self::showBindElement($code, $arItem['TITLE'], $value, $arHtmlControl);
                }
                else if($arItem['TYPE'] === 'htmleditor' && $key === $code){
                    $result .= self::showHtmlEditor($code, $arItem['TITLE'], $value, $arHtmlControl);
                }
            }
        }
        
        $result .= '</table>';
        return $result;
	}

    private static function showString($code, $title, $value, $arHtmlControl)
    {
        $result = '';
        $v = !empty($value) ? $value : '';
        $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td>
                        <input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'"/>
                        <input type="text" value="'.$v.'" name="'.$arHtmlControl["NAME"].'"/>
                    </td>
                </tr>';

        return $result;
    }

    private static function showFile($code, $title, $value, $arHtmlControl): string
    {
        $result = '';
        $fileId = self::getFileID();
        
        if(!empty($fileId)) {
            $result .= '<tr>
                            <td align="right">'.$title.':</td>
                            <td>';
            $result .= CFile::ShowImage($fileId, 200, 200, "border=0", "", true);
            $result .= '        <input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'">
                                <input type="hidden" value="'.$fileId.'" name="'.$arHtmlControl["NAME"].'">   
                            </td>
                        </tr>';
            $result .= '<tr>
                            <td align="right">Удалить файл</td>
                            <td>
                                <input type="checkbox" value="del" name="'.$arHtmlControl["NAME"].'">
                            </td>
                        </tr>';
        } 
        else {
            $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td>
                        <input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'"/>';
            $result .= '<input type="file" name="'.$code.'">';
            $result .=   '</td>
                </tr>';
        }
            
        return $result;
    }

    private static function getFileID()
    {
        $result = "";
        $arOrder = ["ID" => "ASC"];
        $arFilter = ["ORIGINAL_NAME" => $_SESSION['file_orig_name'], "MODULE_ID" => "shark.cprop"];
        $res = CFile::GetList($arOrder, $arFilter)->Fetch();

        $address = $_SERVER["DOCUMENT_ROOT"] . "/upload/" . $res["SUBDIR"] . "/" . $res["FILE_NAME"];

        if (file_exists($address)) {
            $result = $res["ID"];
            $_SESSION['file_orig_name'] = "";
        } else {
            $result = "";
        }

        return $result;
    }

    public static function showTextarea($code, $title, $value, $arHtmlControl)
    {
        $result = '';

        $v = !empty($value) ? $value : '';
        $result .= '<tr>
                    <td align="right" valign="top">'.$title.': </td>
                    <td>
                        <input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'">
                        <textarea rows="8" name="'.$arHtmlControl["NAME"].'">'.$v.'</textarea>
                    </td>
                </tr>';

        return $result;
    }

    public static function showDate($code, $title, $value, $arHtmlControl)
    {
        $result = '';

        $v = !empty($value) ? $value : '';
        $result .= '<tr>
                        <td align="right" valign="top">'.$title.': </td>
                        <td>
                            <table>
                                <tr>
                                    <td style="padding: 0;">
                                        <div class="adm-input-wrap adm-input-wrap-calendar">
                                            <input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'">
                                            <input class="adm-input adm-input-calendar" type="datetime-local" name="'.$arHtmlControl['NAME'].'" size="23" value="'.$v.'">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>';

        return $result;
    }

    public static function showBindElement($code, $title, $value, $arHtmlControl)
    {
        $result = '';

        $v = !empty($value) ? $value : '';

        $elUrl = '';
        if(!empty($v)){
            $arElem = \CIBlockElement::GetList([], ['ID' => $v],false, ['nPageSize' => 1], ['ID', 'IBLOCK_ID', 'IBLOCK_TYPE_ID', 'NAME'])->Fetch();
            if(!empty($arElem)){
                $elUrl .= '<a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$arElem['IBLOCK_ID'].'&ID='.$arElem['ID'].'&type='.$arElem['IBLOCK_TYPE_ID'].'">'.$arElem['NAME'].'</a>';
            }
        }

        $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td>
                        <input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'">
                        <input name="'.$arHtmlControl['NAME'].'" id="'.$arHtmlControl['NAME'].'" value="'.$v.'" size="8" type="text" class="mf-inp-bind-elem">
                        <input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID=0&n='.$arHtmlControl['NAME'].'&k='.$code.'\', 900, 700);">&nbsp;
                        <span>'.$elUrl.'</span>
                    </td>
                </tr>';

        return $result;
    }
    
    public static function showHtmlEditor($code, $title, $value, $arHtmlControl)
    {
        $result = '';
        $v = !empty($value) ? $value : '';
        ob_start();

        $result .= '<input type="hidden" value="'.$code.'" name="'.$arHtmlControl["NAME"].'">';
        if (CModule::IncludeModule("fileman")) {
            $LHE = new CHTMLEditor;
            $LHE->Show(array(
            'name' => $arHtmlControl["NAME"],
            'id' => preg_replace("/[^a-z0-9]/i", '', "editorHtml"),
            'inputName' => $arHtmlControl["NAME"],
            'content' => $v,
            'width' => '100%',
            'minBodyWidth' => 350,
            'normalBodyWidth' => 555,
            'height' => '200',
            'bAllowPhp' => false,
            'limitPhpAccess' => false,
            'autoResize' => true,
            'autoResizeOffset' => 40,
            'useFileDialogs' => false,
            'saveOnBlur' => true,
            'showTaskbars' => false,
            'showNodeNavi' => false,
            'askBeforeUnloadPage' => true,
            'bbCode' => false,
            'siteId' => SITE_ID,
            'controlsMap' => array(
                array('id' => 'Bold', 'compact' => true, 'sort' => 80),
                array('id' => 'Italic', 'compact' => true, 'sort' => 90),
                array('id' => 'Underline', 'compact' => true, 'sort' => 100),
                array('id' => 'Strikeout', 'compact' => true, 'sort' => 110),
                array('id' => 'RemoveFormat', 'compact' => true, 'sort' => 120),
                array('id' => 'Color', 'compact' => true, 'sort' => 130),
                array('id' => 'FontSelector', 'compact' => false, 'sort' => 135),
                array('id' => 'FontSize', 'compact' => false, 'sort' => 140),
                array('separator' => true, 'compact' => false, 'sort' => 145),
                array('id' => 'OrderedList', 'compact' => true, 'sort' => 150),
                array('id' => 'UnorderedList', 'compact' => true, 'sort' => 160),
                array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
                array('separator' => true, 'compact' => false, 'sort' => 200),
                array('id' => 'InsertLink', 'compact' => true, 'sort' => 210),
                array('id' => 'InsertImage', 'compact' => false, 'sort' => 220),
                array('id' => 'InsertVideo', 'compact' => true, 'sort' => 230),
                array('id' => 'InsertTable', 'compact' => false, 'sort' => 250),
                array('separator' => true, 'compact' => false, 'sort' => 290),
                array('id' => 'Fullscreen', 'compact' => false, 'sort' => 310),
                array('id' => 'More', 'compact' => true, 'sort' => 400)
                ),
            ));
        } else {
            showError("Визуальный редактор не удалось подключить");
        }

        $result .= '<tr>
                <td align="right">'.$title.': </td>
                <td>';

        $result .= ob_get_contents();

        $result .= '
                </td>
            </tr>';
        ob_end_clean();

        return $result;
    }

	private static function getOptionList($selected = 'string')
	{
		$result = "";
        $arOption = [
            "string" => Loc::getMessage("SHARK_CPROP_FIELD_TYPE_STRING"),
            "file" => Loc::getMessage("SHARK_CPROP_FIELD_TYPE_FILE"),
            "text" => Loc::getMessage("SHARK_CPROP_FIELD_TYPE_TEXT"),
            "date" => Loc::getMessage("SHARK_CPROP_FIELD_TYPE_DATE"),
            "element" => Loc::getMessage("SHARK_CPROP_FIELD_TYPE_ELEMENT"),
            "htmleditor" => Loc::getMessage("SHARK_CPROP_FIELD_TYPE_HTML_EDITOR")
        ];

        foreach ($arOption as $code => $name){
            $s = "";
            if($code === $selected){
                $s = "selected";
            }

            $result .= '<option value="'.$code.'" '.$s.' name="'.$code.'">'.$name.'</option>';
        }

        return $result;
	}

	/* Styles and etc */

	private static function showCss()
    {
        if(!self::$showedCss) {
            self::$showedCss = true;
            ?>
            <style>
                .cl {cursor: pointer;}
                .mf-gray {color: #797777;}
                .mf-fields-list {display: none; padding-top: 20px; padding-bottom: 10px; margin-bottom: 10px!important; margin-left: -300px!important; border-bottom: 1px #e0e8ea solid!important;}
                .mf-fields-list.active {display: block;}
                .mf-fields-list td {padding-bottom: 5px;}
                .mf-fields-list td:first-child {width: 300px; color: #616060;}
                .mf-fields-list td:last-child {padding-left: 5px;}
                .mf-fields-list input[type="text"] {width: 350px!important;}
                .mf-fields-list textarea {min-width: 350px; max-width: 650px; color: #000;}
                .mf-fields-list img {max-height: 150px; margin: 5px 0;}
                .mf-img-table {background-color: #e0e8e9; color: #616060; width: 100%;}
                .mf-fields-list input[type="text"].adm-input-calendar {width: 170px!important;}
                .mf-file-name {word-break: break-word; padding: 5px 5px 0 0; color: #101010;}
                .mf-fields-list input[type="text"].mf-inp-bind-elem {width: unset!important;}
            </style>
            <?
        }
    }

    private static function showJs()
    {
        $showText = Loc::getMessage('SHARK_CPROP_SHOW_TEXT');
        $hideText = Loc::getMessage('SHARK_CPROP_HIDE_TEXT');

        CJSCore::Init(array("jquery"));
        if(!self::$showedJs) {
            self::$showedJs = true;
            ?>
            <script>
                $(document).on('click', 'a.mf-toggle', function (e) {
                    e.preventDefault();

                    var table = $(this).closest('tr').find('table.mf-fields-list');
                    $(table).toggleClass('active');
                    if($(table).hasClass('active')){
                        $(this).text('<?=$hideText?>');
                    }
                    else{
                        $(this).text('<?=$showText?>');
                    }
                });

                $(document).on('click', 'a.mf-delete', function (e) {
                    e.preventDefault();

                    var textInputs = $(this).closest('tr').find('input[type="text"]');
                    $(textInputs).each(function (i, item) {
                        $(item).val('');
                    });

                    var textarea = $(this).closest('tr').find('textarea');
                    $(textarea).each(function (i, item) {
                        $(item).text('');
                    });

                    var checkBoxInputs = $(this).closest('tr').find('input[type="checkbox"]');
                    $(checkBoxInputs).each(function (i, item) {
                        $(item).attr('checked', 'checked');
                    });

                    $(this).closest('tr').hide('slow');
                });
            </script>
            <?
        }
    }

    private static function showJsForSetting($inputName)
    {
        CJSCore::Init(array("jquery"));
        ?>
        <script>
            function addNewRows() {
                $("#many-fields-table").append('' +
                    '<tr valign="top">' +
                    '<td><input type="text" class="inp-code" size="20"></td>' +
                    '<td><input type="text" class="inp-title" size="35"></td>' +
                    '<td><input type="text" class="inp-sort" size="5" value="500"></td>' +
                    '<td><select class="inp-type"><?=self::getOptionList()?></select></td>' +
                    '</tr>');
            }


            $(document).on('change', '.inp-code', function(){
                var code = $(this).val();

                if(code.length <= 0){
                    $(this).closest('tr').find('input.inp-title').removeAttr('name');
                    $(this).closest('tr').find('input.inp-sort').removeAttr('name');
                    $(this).closest('tr').find('select.inp-type').removeAttr('name');
                }
                else{
                    $(this).closest('tr').find('input.inp-title').attr('name', '<?=$inputName?>[' + code + '_TITLE]');
                    $(this).closest('tr').find('input.inp-sort').attr('name', '<?=$inputName?>[' + code + '_SORT]');
                    $(this).closest('tr').find('select.inp-type').attr('name', '<?=$inputName?>[' + code + '_TYPE]');
                }
            });

            $(document).on('input', '.inp-sort', function(){
                var num = $(this).val();
                $(this).val(num.replace(/[^0-9]/gim,''));
            });
        </script>
        <?
    }

    private static function showCssForSetting()
    {
        if(!self::$showedCss) {
            self::$showedCss = true;
            ?>
            <style>
                .many-fields-table {margin: 0 auto; /*display: inline;*/}
                .mf-setting-title td {text-align: center!important; border-bottom: unset!important;}
                .many-fields-table td {text-align: center;}
                .many-fields-table > input, .many-fields-table > select{width: 90%!important;}
                .inp-sort{text-align: center;}
                .inp-type{min-width: 125px;}
            </style>
            <?
        }
    }
}
