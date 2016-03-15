<?php

namespace Pin;

class ReportController extends \Base\PermissionController {

    /**
     * @var null|array
     */
    public $errors;

    public function init() {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $this->noLayout(true);
        }
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function indexAction() {
        $request = $this->getRequest();
        $data = array();

        $self_data = \User\User::getUserData();

        $pin_id = $request->getRequest('pin_id');

        $pinTable = new \Pin\Pin();
        $pin_info = $pinTable->fetchRow(array('id = ?' => $pin_id));

        if ($request->isPost()) {
            if (!$self_data->id) {
                $return['location'] = $this->url(array('controller' => 'login'), 'user_c');
            } else {
                $report_category = $request->getPost('report_category');
                $validator = new \Core\Form\Validator(array(
                    'translate' => $this->_
                ));
                if ($request->getPost('report_category') == -1) {
                    $report_category = null;
                    $validator->addText('report_message', array(
                        'min' => 3,
                        'error_text_min' => $this->_('Message must contain more than %d characters')
                    ));
                }
                if ($validator->validate()) {
                    $pinReport = new \Pin\PinReport();
                    $new = $pinReport->fetchNew();
                    $new->report_category_id = $report_category;
                    $new->user_id = $self_data->id;
                    $new->date_added = \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->toString();
                    $new->pin_id = $pin_id;
                    $new->message = $request->getPost('report_message');
                    try {
                        $new->save();

                        ////notify
                        $email = new \Helper\Email();
                        $email->addFrom(\Base\Config::get('no_reply'));
                        $email->addTo(\Base\Config::get('site_owner_reply'));
                        $email->addTitle($this->_('New reported pin'));
                        $email->addHtml(sprintf($this->_('Hello, there is new reported pin in %s'), \Meta\Meta::getGlobal('title')));
                        $email->send();
                        /////////

                        $return['reported'] = true;
                    } catch (\Core\Exception $e) {
                        $return['errors']['Exception'] = $e->getMessage();
                    }
                } else {
                    $return['errors'] = $validator->getErrors();
                }
            }
            $this->responseJsonCallback($return);
            exit;
        }

        if (!$request->isXmlHttpRequest()) {
            $this->redirect($this->url(array('controller' => 'login'), 'user_c'));
        }
        if (!$pin_info) {
            $this->forward('error404');
        }

        $categoryTable = new \Pin\PinReportCategory();
        $data['categories'] = $categoryTable->getAll();
        $data['pin'] = $pin_info;

        $this->render('index', $data);
    }

}
