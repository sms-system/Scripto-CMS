<?
global $settings;
$moduleinfo["caption"]="Модуль пользователи";
$moduleinfo["url"]="http://www.scripto.ru";
$moduleinfo["author"]="Scripto";
$moduleinfo["description"]="";
$moduleinfo["version"]="1.0";
$moduleinfo["use_in_all_rubrics"]=true;
$moduleinfo["icon"]="users.png";
$moduleinfo["documentation"]="http://scripto-cms.ru/documentation/additional/users/";

$moduleinfo["mailadmin"]=$settings["mailadmin"];
$moduleinfo["my_url"]="my";
$moduleinfo["register_url"]="register";
$moduleinfo["register"]=true;//включить или выключить регистрацию (true - включить)
$moduleinfo["forgot_url"]="forgot";
$moduleinfo["onpage_admin"]=15;

$moduleinfo["width"]=100;//ширина изображения аватарки пользователя
$moduleinfo["height"]=100;//высота изображения аватарки пользователя
?>