<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use lib\exceptions\UserErrorException;
use Yii;

/**
 * Backend service class for the access control tool widget
 */
class AccessConfigController extends \app\Controllers\AppController
{


  /**
   * Sends an email to the user, either a confirmation email if email has not yet been confirmed, or an information on
   * change of the password
   * @param array|object $data
   * @return qcl_ui_dialog_Alert
   */
  protected function sendInformationEmail($data)
  {
    $data = (object)$data;
    if (!$data->confirmed) {
      $this->sendConfirmationLinkEmail($data->email, $data->namedId, $data->name);
      return \lib\dialog\Alert::create(
        Yii::t('app', "An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
      );
    } else {
      $this->sendPasswordChangeEmail($data->email, $data->namedId, $data->name);
      return \lib\dialog\Alert::create(
        Yii::t('app', "An email has been sent to %s (%s) to inform about the change of password.", $data->name, $data->email)
      );
    }
  }

  /**
   * Sends an email to confirm the registration
   * @param $email
   * @param $username
   * @param $name
   * @param $tmpPasswd
   */
  protected function sendConfirmationLinkEmail($email, $username, $name, $tmpPasswd = null)
  {
    $app = $this->getApplication();
    $applicationTitle = $this->getApplicationTitle();
    $adminEmail = $app->config->getIniValue("email.admin");
    $confirmationLink = qcl_server_JsonRpcRestServer::getJsonRpcRestUrl(
      Yii::$app->controller->id, "confirmEmail", $username
    );

    // compose mail
    $subject = Yii::t('app', "Your registration at %s", $applicationTitle);
    $body = Yii::t('app', "Dear %s,", $name);
    $body .= "\n\n" . Yii::t('app', "You have been registered as user '%s' at '%s'.", $username, $applicationTitle);
    if ($tmpPasswd) {
      $body .= "\n\n" . Yii::t('app', "Your temporary password is '%s'. You will be asked to change it after your first login.", $tmpPasswd);
    }
    $body .= "\n\n" . Yii::t('app', "Please confirm your account by visiting the following link:");
    $body .= "\n\n" . $confirmationLink;
    $body .= "\n\n" . Yii::t('app', "Thank you.");

    // send mail
    $mail = new qcl_util_system_Mail(array(
      'senderEmail' => $adminEmail,
      'recipient' => $name,
      'recipientEmail' => $email,
      'subject' => $subject,
      'body' => $body
    ));
    $mail->send();
  }

  /**
   * Returns the (custom) title of the application
   * @return string
   */
  protected function getApplicationTitle()
  {
    return Yii::$app->config->getPreference("application.title");
  }

  /**
   * Sends an email with information on the change of password.
   * @param $email
   * @param $username
   * @param $name
   */
  protected function sendPasswordChangeEmail($email, $username, $name)
  {
    $app = $this->getApplication();
    $applicationTitle = $this->getApplicationTitle();
    $adminEmail = $app->config->getIniValue("email.admin");

    // compose mail
    $subject = Yii::t('app', "Password change at %s", $applicationTitle);
    $body = Yii::t('app', "Dear %s,", $name);
    $body .= "\n\n" . Yii::t('app', "This is to inform you that you or somebody else has changed the password at %s.", $applicationTitle);
    $body .= "\n\n" . Yii::t('app', "If this is not what you wanted, please reset your password immediately by clicking on the following link:");
    $body .= "\n\n" . $this->generateResetPasswordURL($email);

    // send email
    $mail = new qcl_util_system_Mail(array(
      'senderEmail' => $adminEmail,
      'recipient' => $name,
      'recipientEmail' => $email,
      'subject' => $subject,
      'body' => $body
    ));
    $mail->send();
  }

  /**
   * Returns an URL which can be used to reset the password.
   * @param $email
   * @return string
   */
  protected function generateResetPasswordURL($email)
  {
    return qcl_server_Server::getUrl() .
      "?service=" . Yii::$app->controller->id .
      "&method=" . "resetPassword" .
      "&params=" . $email . "," . $this->createAndStoreNonce() .
      "&sessionId=" . $this->getSessionId();
  }

  /**
   * Create nonce and store it in the PHP session
   * @return string The nonce
   */
  protected function createAndStoreNonce()
  {
    $nonce = md5(uniqid(rand(), true));
    $_SESSION['EMAIl_RESET_NONCE'] = $nonce;
    return $nonce;
  }

  /**
   * Sends an informational email to different groups of
   * @param $type
   * @param $namedId
   * @return array
   */
  public function actionEmailCompose($type, $namedId, $subject = "", $body = "")
  {
    $this->requirePermission("access.manage");

    if (!in_array($type, array("user", "group"))) {
      throw new \lib\exceptions\UserErrorException("Email can only be sent to users and groups.");
    }

    $model = $this->getElementModel($type);
    $model->load($namedId);

    $emails = array();
    $names = array();

    switch ($type) {
      case "user":
        $email = $model->getEmail();
        if (!trim($email)) {
          throw new \lib\exceptions\UserErrorException(Yii::t('app', "The selected user has no email address."));
        }
        $emails[] = $email;
        $names[] = $model->getName();
        break;

      case "group":
        $userModel = $this->getElementModel("user");
        try {
          $userModel->findLinked($model);
        } catch (qcl_data_model_RecordNotFoundException $e) {
          throw new \lib\exceptions\UserErrorException(Yii::t('app', "The selected group has no members."));
        }
        while ($userModel->loadNext()) {
          $email = $userModel->getEmail();
          if (trim($email)) {
            $emails[] = $email;
            $names[] = $userModel->getName();
          }
        }
    }

    $number = count($emails);
    if ($number == 0) {
      throw new \lib\exceptions\UserErrorException(Yii::t('app', "No email address found."));
    }

    $modelMap = $this->modelData();
    $recipients = Yii::t('app', $modelMap[$type]['dialogLabel']) . " '" . $model->getName() . "'";
    $message = "<h3>" .
      Yii::t('app',
        "Email to %s",
        $recipients . ($type == "group" ? " ($number recipients)" : "")
      ) .
      "</h3>" .
      (($type == "group") ? "<p>" . implode(", ", $names) . "</p>" : "");

    $formData = array(
      "subject" => array(
        "label" => Yii::t('app', "Subject"),
        "type" => "TextField",
        "width" => 400,
        "value" => $subject
      ),
      "body" => array(
        "label" => Yii::t('app', "Message"),
        "type" => "TextArea",
        "lines" => 10,
        "value" => $body
      )
    );

    return \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "confirmSendEmail",
      array($this->shelve($type, $namedId, $emails, $names))
    );
  }

  public function actionEmailConfirm($data, $shelfId)
  {

    if (!$data) {
      $this->unshelve($shelfId);
      return "CANCELLED";
    }

    list($type, $namedId, $emails, $names) = $this->unshelve($shelfId, true);

    if (!trim($data->subject)) {
      return \lib\dialog\Alert::create(
        Yii::t('app', "Please enter a subject."),
        Yii::$app->controller->id, "correctEmail",
        array($shelfId, $data)
      );
    }

    if (!trim($data->body)) {
      return \lib\dialog\Alert::create(
        Yii::t('app', "Please enter a message."),
        Yii::$app->controller->id, "correctEmail",
        array($shelfId, $data)
      );
    }

    return \lib\dialog\Confirm::create(
      Yii::t('app', "Send email to %s recipients?", count($emails)), null,
      Yii::$app->controller->id, "sendEmail",
      array($shelfId, $data)
    );
  }

  public function actionEmailCorrect($dummy, $shelfId, $data)
  {
    list($type, $namedId, $emails, $names) = $this->unshelve($shelfId);
    return $this->method_composeEmail($type, $namedId, $data->subject, $data->body);
  }

  public function actionEmailSend($confirm, $shelfId, $data)
  {
    list($type, $namedId, $emails, $names) = $this->unshelve($shelfId);

    if (!$confirm) {
      return "CANCELLED";
    }

    $subject = $data->subject;
    $body = $data->body;

    foreach ($emails as $index => $email) {
      $name = $names[$index];
      $adminEmail = $this->getApplication()->getIniValue("email.admin");
      $mail = new qcl_util_system_Mail(array(
        'senderEmail' => $adminEmail,
        'recipient' => $name,
        'recipientEmail' => $email,
        'subject' => $subject,
        'body' => $body
      ));


      $mail->send();
    }

    return \lib\dialog\Alert::create(Yii::t('app', "Sent email to %s recipients", count($emails)));
  }

  public function actionMissingPassword($namedId)
  {
    return $this->edit("user", $namedId);
  }

  /**
   * Service to confirm a registration via email
   * @param $namedId
   */
  public function actionConfirmRegistration($namedId)
  {
    $app = $this->getApplication();
    $userModel = $app->getAccessController()->getUserModel();
    header('Content-Type: text/html; charset=utf-8');
    try {
      $userModel->findWhere(array(
        'namedId' => $namedId
      ));
      while ($userModel->loadNext()) {
        $userModel->set("confirmed", true);
        $userModel->save();
      }
      $msg1 = Yii::t('app', "Thank you, %s, your email address has been confirmed.", $userModel->getName());
      $msg2 = Yii::t('app',
        "You can now log in as user '%s' at <a href='%s'>this link</a>",
        $userModel->namedId, $app->getClientUrl()
      );
      echo "<html><p>$msg1<p>";
      echo "<p>$msg2</p></html>";
      exit;
    } catch (qcl_data_model_RecordNotFoundException $e) {
      // should never be the case
      echo "Invalid username '$namedId'";
      exit;
    }
  }

  /**
   * Displays a dialog to reset the password
   */
  public function actionResetPasswordDialog()
  {
    $msg = Yii::t('app', "Please enter your email address. You will receive a message with a link to reset your password.");
    \lib\dialog\Prompt::create($msg, "", Yii::$app->controller->id, "password-reset-email");
  }

  /**
   * Service to send password reset email
   * @param $email
   * @return string
   * @throws \Exception
   */
  public function actionPasswortResetEmail($email)
  {
    if ($email == false) return "CANCELLED";

    $userModel = $this->getUserModelFromEmail($email);
    $name = $userModel->get("name");
    $adminEmail = $this->getApplication()->getIniValue("email.admin");
    $applicationTitle = $this->getApplicationTitle();

    // compose mail
    $subject = Yii::t('app', "Password reset at %s", $applicationTitle);
    $body = Yii::t('app', "Dear %s,", $name);
    $body .= "\n\n" . Yii::t('app', "This is to inform you that you or somebody else has requested a password reset at %s.", $applicationTitle);
    $body .= "\n\n" . Yii::t('app', "If this is not what you wanted, you can ignore this email. Your account is safe.");
    $body .= "\n\n" . Yii::t('app', "If you have requested the reset, please click on the following link:");
    $body .= "\n\n" . $this->generateResetPasswordURL($email);

    // send
    $mail = new qcl_util_system_Mail(array(
      'senderEmail' => $adminEmail,
      'recipient' => $name,
      'recipientEmail' => $email,
      'subject' => $subject,
      'body' => $body
    ));
    $mail->send();

    return \lib\dialog\Alert::create(
      Yii::t('app', "An email has been sent with information on the password reset.")
    );
  }

  /**
   * Given an email address, returns the (first) user record that matches this address
   * @param $email
   * @return qcl_access_model_User
   * @throws \Exception
   */
  protected function getUserModelFromEmail($email)
  {
    try {
      qcl_assert_valid_email($email);
    } catch (InvalidArgumentException $e) {
      throw new \Exception(
        Yii::t('app', "%s is not a valid email address", $email)
      );
    }
    $userModel = $this->getAccessController()->getUserModel();
    try {
      $userModel->loadWhere(array("email" => $email));
    } catch (qcl_data_model_RecordNotFoundException $e) {
      throw new \Exception(
        Yii::t('app', "No user found for email address %s", $email)
      );
    }
    return $userModel;
  }

  /**
   * Service to reset email. Called by a REST request
   * @param $email
   * @param $nonce
   */
  public function actionResetPassword($email, $nonce)
  {
    $storedNonce = $this->retrieveAndDestroyStoredNonce();
    header('Content-Type: text/html; charset=utf-8');
    if (!$storedNonce or $storedNonce != $nonce) {
      echo Yii::t('app', "Access denied.");
      exit;
    }

    // set new temporary password with length 7 (this will enforce a password change)
    $password = qcl_generate_password(7);
    $userModel = $this->getUserModelFromEmail($email);
    $userModel->set("password", $password)->save();

    // message to the user
    $url = $this->getApplication()->getClientUrl();
    $name = $userModel->getNamedId();
    $msg = Yii::t('app', "%s, your password has been reset.", $userModel->get("name"));
    $msg .= "\n\n" . Yii::t('app', "Your username is '%s' and your temporary password is '%s'.", $name, $password);
    $msg .= "\n\n" . Yii::t('app', "Please <a href='%s'>log in</a> and change the password now.", $url);
    echo "<html>" . nl2br($msg) . "</html>";
    exit;
  }

  /**
   * Retrieves the stored nonce and destroys in the PHP session.
   * @return string
   */
  protected function retrieveAndDestroyStoredNonce()
  {
    $storedNonce = $_SESSION['EMAIl_RESET_NONCE'];
    unset($_SESSION['EMAIl_RESET_NONCE']);
    return $storedNonce;
  }

}