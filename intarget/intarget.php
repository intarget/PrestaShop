<?php

/**
 * 2015 inTarget
 * @author    inTarget RU <https://intarget.ru/>
 * @copyright 2015 inTarget RU
 * @license   GNU General Public License, version 2
 */
class Intarget extends Module
{

    public $success_reg = 0;

    public function __construct()
    {
        $this->name = 'intarget';
        $this->tab = 'seo';
        $this->config_form = 'password';
        $this->version = '1.0.1';
        $this->author = 'inTarget Team';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('inTarget module');
        $this->description = $this->l('Сервис повышения продаж и аналитика посетителей сайта');
        $this->confirmUninstall = $this->l('Вы действительно хотите удалить модуль inTarget?');
    }

    public function install()
    {
        //задаём значение переменных по умолчанию
        if (!Configuration::get('intarget_email')) Configuration::updateValue('intarget_email', '');
        if (!Configuration::get('intarget_key')) Configuration::updateValue('intarget_key', '');
        if (!Configuration::get('intarget_id')) Configuration::updateValue('intarget_id', '');

        return parent::install() &&
        $this->registerHook('displayTop') &&
        $this->registerHook('ActionCustomerAccountAdd') &&
        $this->registerHook('displayFooter') &&
        $this->registerHook('backOfficeHeader') &&
        $this->registerHook('displayProductButtons') &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->installDB();
    }

    public function installDB()
    {
        $return = true;
        $return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'intarget_table` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` int(10) unsigned NOT NULL ,
				`user-reg` int(10),
				PRIMARY KEY (`id`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );
        return $return;
    }

    public function uninstall()
    {
        if (Configuration::get('intarget_id')) Configuration::updateValue('intarget_id', '');
        if (Configuration::get('intarget_key')) Configuration::updateValue('intarget_key', '');
        if (Configuration::get('intarget_email')) Configuration::updateValue('intarget_email', '');

        return parent::uninstall() && $this->uninstallDB();
    }

    public function uninstallDB()
    {
        $return = true;
        $return &= Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'intarget_table`');
        return $return;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $intarget_email = Tools::getValue('intarget_email');
            $intarget_key = Tools::getValue('intarget_key');
            if (!empty($intarget_key) && !empty($intarget_email)) {
                Configuration::updateValue('intarget_key', $intarget_key);
                Configuration::updateValue('intarget_email', $intarget_email);
                $output = $this->displayConfirmation($this->l('Поздравляем, сайт успешно привязан к аккаунту') . ' <a href="https://intarget.ru">inTarget</a>') . $this->displayFormSuccess() . $output;
            }
        }
        if (!Configuration::get('intarget_key') && !Configuration::get('intarget_email')) {
            return $this->displayError($this->l('Заполните обязательные поля')) . $this->displayForm() . $output;
        } else {
            $key = Configuration::get('intarget_key');
            $email = Configuration::get('intarget_email');
            $host = $this->CurrentUrl();

            if (!Configuration::get('intarget_id')) {
                $result = $this->GetInfoFromIntarget($key, $email, $host);
                if ($result['error']) {
                    $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
                    $output = $this->displayError($result['error']) . $this->displayForm() . $output;
                } else {
                    if ($result['ok']) {
                        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
                        $output = $this->displayConfirmation($this->l('Поздравляем, сайт успешно привязан к аккаунту') . ' <a href="https://intarget.ru">inTarget</a>') . $this->displayFormSuccess() . $output;
                    }
                }
            } else {
                $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
                $output = $this->displayConfirmation($this->l('Поздравляем, сайт успешно привязан к аккаунту') . ' <a href="https://intarget.ru">inTarget</a>') . $this->displayFormSuccess() . $output;
            }

            return $output;
        }
    }

    /**
     * Возвращает url
     */
    public function CurrentUrl()
    {
        $url = 'http';
        if (isset($_SERVER['HTTPS']))
            if ($_SERVER['HTTPS'] == 'on')
                $url .= 's';
        $url .= '://';
        if ($_SERVER['SERVER_PORT'] != '80')
            $url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']; else
            $url .= $_SERVER['SERVER_NAME'];

        return $url;
    }

    public function displayForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        //ширина полей в админке модуля
        $ulogin_col = '6';
        // Описываем поля формы для страници настроек
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('inTarget') . '  &mdash; ' . $this->l('Сервис повышения продаж и аналитика посетителей сайта'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Оцените принципиально новый подход к просмотру статистики. Общайтесь со своей аудиторией, продавайте лучше, зарабатываейте больше. И всё это бесплатно!'),
                    'name' => 'text'),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'intarget_email',
                    'required' => true,
                    'col' => $ulogin_col,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Ключ API'),
                    'name' => 'intarget_key',
                    'required' => true,
                    'col' => $ulogin_col,
                ),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Введите email и ключ API из личного кабинета ') . '<a href="http://intarget.ru" target="_blank">inTarget.ru</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Если вы ещё не зарегистрировались в сервисе inTarget это можно сделать по ссылке ') . '<a href="http://intarget.ru" target="_blank">inTarget.ru</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Служба технической поддержки: ') . '<a href="mailto:plugins@intarget.ru">plugins@intarget.ru</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('PrestaShop inTarget ver.') . $this->version,
                    'name' => 'text')
            ),
            'submit' => array('title' => $this->l('Сохранить настройки'),
                'class' => 'button'));
        $helper = new HelperForm();
        // Module, token и currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        // Язык
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        // Заголовок и toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false - убирает toolbar
        $helper->toolbar_scroll = true;      // toolbar виден всегда наверху экрана.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Сохранить'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules')
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Венуться к списку')
            )
        );

        // Загружаем нужные нам значения из базы
        $helper->fields_value['intarget_key'] = Configuration::get('intarget_key');
        $helper->fields_value['intarget_email'] = Configuration::get('intarget_email');
        return $helper->generateForm($fields_form);
    }

    public function displayFormSuccess()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        //ширина полей в админке модуля
        $ulogin_col = '6';
        // Описываем поля формы для страници настроек
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('inTarget') . '  &mdash; ' . $this->l('Сервис повышения продаж и аналитика посетителей сайта'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Оцените принципиально новый подход к просмотру статистики. Общайтесь со своей аудиторией, продавайте лучше, зарабатываейте больше. И всё это бесплатно!'),
                    'name' => 'text'),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'intarget_email',
                    'col' => $ulogin_col,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Ключ API'),
                    'name' => 'intarget_key',
                    'col' => $ulogin_col,
                ),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Войдите в личный кабинет ') . '<a href="http://intarget.ru" target="_blank">inTarget.ru</a>' . $this->l(' для просмотра статистики.'),
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('Служба технической поддержки: ') . '<a href="mailto:plugins@intarget.ru">plugins@intarget.ru</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $ulogin_col,
                    'desc' => $this->l('PrestaShop inTarget ver.') . $this->version,
                    'name' => 'text')
            ),
            'submit' => array('title' => $this->l('Сохранить настройки'),
                'class' => 'button'));
        $helper = new HelperForm();
        // Module, token и currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        // Язык
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        // Заголовок и toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false - убирает toolbar
        $helper->toolbar_scroll = true;      // toolbar виден всегда наверху экрана.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Сохранить'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules')
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Венуться к списку')
            )
        );

        // Загружаем нужные нам значения из базы
        $helper->fields_value['intarget_key'] = Configuration::get('intarget_key');
        $helper->fields_value['intarget_email'] = Configuration::get('intarget_email');
        return $helper->generateForm($fields_form) . '
        <style>
        .intrg_ok{
            right: 5px;
            top: 6px;
            position: absolute;
            padding-right: 10px;
        }
        </style>
        <script>
        $(document).ready(function () {
                $("input[id=intarget_email]").attr("disabled", "disabled");
                $("input[id=intarget_key]").attr("disabled", "disabled");
                $("input[id=intarget_email]").before("<img title=\"Введен правильный email!\" class=\"intrg_ok\" src=\"' . $this->_path . '/ok.png\">");
                $("input[id=intarget_key]").before("<img title=\"Введен правильный email!\" class=\"intrg_ok\" src=\"' . $this->_path . '/ok.png\">");
             });
            </script>';
    }

    /**
     * Получает Id площадки
     * @param bool $token
     * @return bool|mixed|string
     */
    public function GetInfoFromIntarget($key, $email, $host)
    {
        $ch = curl_init();
        $jsondata = json_encode(array(
                'email' => $email,
                'key' => $key,
                'url' => $host,
                'cms' => 'prestashop'
            )
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Accept: application/json'));
        curl_setopt($ch, CURLOPT_URL, "http://intarget-dev.lembrd.com/api/registration.json");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $server_output = curl_exec($ch);

        $json_result = json_decode($server_output);
        curl_close($ch);

        if (isset($json_result->status)) {
            if (($json_result->status == 'OK')) {
                Configuration::updateValue('intarget_id', $json_result->payload->projectId);
                return array('ok' => $json_result->payload->projectId);
            } elseif ($json_result->status == 'error') {
                if ($json_result->code == '403') $json_result->message = $this->l('Введен неверный ключ API!');
                if ($json_result->code == '500') $json_result->message = $this->l('Невозможно создать проект. Возможно, он уже создан.');
                if ($json_result->code == '404') $json_result->message = $this->l('Данный email ') . $email . $this->l(' не зарегистрирован на сайте http://intarget.ru');
                if (!isset($json_result->code)) $json_result->message = $this->l('Неверный формат данных.');
                return array('error' => $json_result->message);
            }
        }
        return true;
    }

    public $itemjscode = "
        <script type='text/javascript'>
                (function(w, c) {
                    w[c] = w[c] || [];
                    w[c].push(function(inTarget) {
                        inTarget.event('item-view');
                        //debug
                        console.log('inTarget: item view');
                    });
                })(window, 'inTargetCallbacks');
        </script>";

    public $catjscode = "
        <script type='text/javascript'>
                (function(w, c) {
                    w[c] = w[c] || [];
                    w[c].push(function(inTarget) {
                        inTarget.event('cat-view');
                         //debug
                         console.log('inTarget: cat view');
                    });
                })(window, 'inTargetCallbacks');
        </script>";

    public $addtocartjscode = "
        <script type='text/javascript'>

                $(function(){
                    $('.quick-view').click(function(){
                        inTarget.event('item-view');
                        //debug
                        console.log('inTarget: item view');
                    });
                });

                $(function(){
                    $('.ajax_add_to_cart_button').click(function(){
                        inTarget.event('add-to-cart');
                        //debug
                        console.log('inTarget: add-to-cart1');
                    })
                });
        </script>";

    public $ajaxaddtocartjscode = "
        <script type='text/javascript'>
                    document.getElementById('add_to_cart').onclick = function() {
                        inTarget.event('add-to-cart');
                        //debug
                        console.log('inTarget: add-to-cart');
                    };
</script>";

    public $delitemjscode = "
        <script type='text/javascript'>
                $(function(){
                  $('.ajax_cart_block_remove_link').click(function(){
                      inTarget.event('del-from-cart');
                      //debug
                      console.log('inTarget: del ajax item');
                  });
                });

                $(function(){
                    $('.cart_quantity_delete').click(function(){
                        inTarget.event('del-from-cart');
                        //debug
                        console.log('inTarget: del item');
                    });
                });
        </script>";

    public $orderjscode = "
        <script type='text/javascript'>
        (function(w, c) {
            w[c] = w[c] || [];
            w[c].push(function(inTarget) {
                inTarget.event('success-order');
                //debug
                console.log('inTarget: success-order');
            });
        })(window, 'inTargetCallbacks');
        </script>";

    public $regjscode = "
     <script type='text/javascript'>
                (function(w, c) {
                    w[c] = w[c] || [];
                    w[c].push(function(inTarget) {
                        inTarget.event('user-reg');
                        //debug
                        console.log('inTarget: user-reg');
                    });
                })(window, 'inTargetCallbacks');
    </script>";

    static public function intargetjscode($id)
    {
        $jscode = "<script type='text/javascript'>
                    (function(d, w, c) {
                      w[c] = {
                        projectId:" . $id . "
                      };
                      var n = d.getElementsByTagName('script')[0],
                      s = d.createElement('script'),
                      f = function () { n.parentNode.insertBefore(s, n); };
                      s.type = 'text/javascript';
                      s.async = true;
                      s.src = '//rt.intarget-dev.lembrd.com/loader.js';
                      if (w.opera == '[object Opera]') {
                          d.addEventListener('DOMContentLoaded', f, false);
                      } else { f(); }
                    })(document, window, 'inTargetInit');
                </script>";
        return $jscode;
    }


    /**
     * Save form data.
     */
    protected function _postProcess()
    {
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
    }

    public function hookDisplayBackOfficeHeader()
    {
        /* Place your code here. */
    }

    public function hookActionCustomerAccountAdd($params)
    {
//        $context = Context::getContext();
        if ($params['newCustomer']->id) {
            Db::getInstance()->insert('intarget_table', array('user_id' => $params['newCustomer']->id, 'user-reg' => 0));
//            if($params['_POST']['back'] == 'my-account') {
//                $context->cookie->__set('intrgt_reg', 1); //delete
//            }
        }
    }

    public function hookDisplayProductButtons()
    {
        $intargetjscode = '';

        if (Configuration::get('intarget_id')) {
            $intargetjscode .= $this->intargetjscode(Configuration::get('intarget_id'));
            $intargetjscode .= $this->ajaxaddtocartjscode;
        }

        $this->context->smarty->assign('intargetjscode', $intargetjscode);
        return $this->display(__FILE__, 'intarget.tpl');

    }

    /* Вывод кода в шапке */
    public function hookDisplayTop($params)
    {
        $intargetjscode = '';

        $currcontroller = Tools::strtolower(get_class($this->context->controller));
        if (Configuration::get('intarget_id')) {
            $intargetjscode .= $this->intargetjscode(Configuration::get('intarget_id'));

            $intargetjscode .= $this->addtocartjscode;
            $intargetjscode .= $this->delitemjscode;

            if ($currcontroller == 'productcontroller') {
                $intargetjscode .= $this->itemjscode;
                $intargetjscode .= $this->ajaxaddtocartjscode;
            }

            if ($currcontroller == 'categorycontroller') {
                $intargetjscode .= $this->catjscode;
            }

            $context = Context::getContext();
            if ($currcontroller == 'orderconfirmationcontroller') {
                $current_order = Tools::getValue('id_order');
                if (!empty($current_order) && $current_order != $context->cookie->intrgt_idord) {
                    $this->context->cookie->__set('intrgt_idord', $current_order);
                    $intargetjscode .= $this->orderjscode;
                }
            }

            if ($currcontroller == 'myaccountcontroller') {
//                if (isset($context->cookie->intrgt_reg) && !empty($context->cookie->intrgt_reg)) {
//                    $intargetjscode .= $this->regjscode;
//                    $context->cookie->__set('intrgt_reg', false);
//                }
                if ($context->customer->isLogged()) {
                    $current_user = (int)$context->customer->id;
                    $intrg_res = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'intarget_table WHERE user_id = ' . $current_user);
                    if ($intrg_res['user-reg'] == 0) {
                        $intargetjscode .= $this->regjscode;
                        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'intarget_table SET `user-reg` = 2 WHERE id = ' . $intrg_res['id']);
                    }
                }
            }

            if ($currcontroller == 'addresscontroller') {
//                if (!isset($context->cookie->intrgt_reg))
//                    $context->cookie->__set('intrgt_reg', 1);
                if(Tools::getValue('back') == 'order?step=1' || Tools::getValue('back') == 'order.php?step=1') {
//                    if (isset($context->cookie->intrgt_reg) && !empty($context->cookie->intrgt_reg)) {
//                        $intargetjscode .= $this->regjscode;
//                        $context->cookie->__set('intrgt_reg', false);
//                    }
                    if ($context->customer->isLogged()) {
                        $current_user = (int)$context->customer->id;
                        $intrg_res = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'intarget_table WHERE user_id = ' . $current_user);
                        if ($intrg_res['user-reg'] == 0) {
                            $intargetjscode .= $this->regjscode;
                            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'intarget_table SET `user-reg` = 2 WHERE id = ' . $intrg_res['id']);
                        }
                    }
                }
            }
        }
        $this->context->smarty->assign('intargetjscode', $intargetjscode);
        return $this->display(__FILE__, 'intarget.tpl');
    }
}