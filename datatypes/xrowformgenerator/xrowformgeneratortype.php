<?php

/**
 * class xrowFormGenerator datatype generates
 * dynamic forms per content object
 * It supports various types of formular data
 * @author Georg Franz, georg@xrow.de
 */

class xrowFormGeneratorType extends eZDataType
{
    const DATA_TYPE_STRING = "xrowformgenerator";

    function xrowFormGeneratorType()
    {
        $this->eZDataType( self::DATA_TYPE_STRING, ezpI18n::tr( 'kernel/classes/datatypes', "Form generator", 'Datatype name' ),
                           array( 'serialize_supported' => false ) );
    }

    /*
     * unset the content cache at storing the object attribute
     */
    function storeObjectAttribute( $objectAttribute )
    {
        if ( isset( $GLOBALS['XrowFormCache'] ) )
        {
            unset( $GLOBALS['XrowFormCache'] );
        }
        return true;
    }

    /*!
     Sets the default value.
    */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $dataText = $originalContentObjectAttribute->attribute( "data_text" );
            $contentObjectAttribute->setAttribute( "data_text", $dataText );
        }
    }

    /*
     * The input of the form setup is always valid.
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        //Checking the Double-Optin-Funktion
        if ( $http->hasPostVariable('XrowFormOptin' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $optin_attribute= $http->postVariable('XrowFormOptin' . $contentObjectAttribute->attribute( 'id' ) );
        }
        
        if ( $http->hasPostVariable('XrowFormElementType' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $num_emails= $http->postVariable('XrowFormElementType' . $contentObjectAttribute->attribute( 'id' ) );
        }
        
        $i=0;
        foreach($num_emails as $num_email)
        {
            if($num_email == "email")
            {
                $i++;
            }
        }
        
        if($optin_attribute == "1" && $i != 1 )
        {
            $contentObjectAttribute->setValidationError(ezpI18n::tr( 'xrowformgenerator/mail', "please insert a Email Field." ));
            return eZInputValidator::STATE_INVALID;
        }else{
            return eZInputValidator::STATE_ACCEPTED;
        }
    }

    function validateCollectionAttributeHTTPInput( $http, $base, $objectAttribute )
    {
        $content = $objectAttribute->content();
        if ( $content['has_http_input'] == true
             and $content['has_error'] == false )
        {
            return eZInputValidator::STATE_ACCEPTED;
        }
        return eZInputValidator::STATE_INVALID;
    }

    function strip($value)
    {
        return is_array($value) ? array_map('xrowFormGeneratorType::strip', $value) : strip_tags($value);
    }

    function strip_allowable_tag($value)
    {
        return is_array($value) ? array_map('xrowFormGeneratorType::strip_allowable_tag', $value) : strip_tags($value,"<a><br><b>");
    }

    /*
     * Fetches the input of the form generator config
     */
    function httpConfigInput( eZHTTPTool $http, eZContentObjectAttribute $attribute )
    {
        $id = $attribute->attribute( "id" );
        $data_array['use_captcha'] = false;
        $data_array['show_anzeige'] = false;
        $data_array['optin'] = false;
        $data_array['button_text'] = "";
        $tplKey = "XrowFormElementArray" . $id;
        if ( $http->hasPostVariable( $tplKey ) )
        {
            $formElementArray = self::strip( $http->postVariable( $tplKey ) );
            $data = array();
            $tplKeyArray = array( 'XrowFormCaptcha' => 'use_captcha',
                                  'XrowFormAmount' => 'show_amount',
                                  'XrowFormReceiver' => 'receiver',
                                  'XrowFormSubject' => 'subject',
                                  'XrowFormSender' => 'sender',
                                  'XrowFormButton' => 'button_text',
                                  'XrowFormAnzeige' => 'show_anzeige',
                                  'XrowFormOptin' => 'optin',
                                  'XrowCampaignID' => 'campaign_id');
            foreach( $tplKeyArray as $tplKeyIndex => $tplKeyItem )
            {
                if ( $http->hasPostVariable( $tplKeyIndex . $id ) )
                {
                    $data_array[$tplKeyItem] = self::strip( $http->postVariable( $tplKeyIndex . $id ) );
                }
            }

            // form config array
            $keyArray = array( "XrowFormElementType" . $id => "type",
                               "XrowFormElementName" . $id => "name",
                               "XrowFormElementDefault" . $id => "def",
                               "XrowFormElementCRM" . $id => "crm",
                               "XrowFormElementCRMClass" . $id => "crmclass",
                               "XrowFormElementMin" . $id => "min",
                               "XrowFormElementMax" . $id => "max",
                               "XrowFormElementStep" . $id => "step",
                               "XrowFormElementDesc" . $id => "desc",
                               "XrowFormElementReq" . $id => "req",
                               "XrowFormElementVal" . $id => "val",
                               "XrowFormElementUnique" . $id => "unique",
                               "XrowFormElementOptionType" . $id => "option_type",
                               "XrowFormElementOptionArray" . $id => "option",
                               "XrowFormElementOption" . $id => "option_name",
                               "XrowFormElementOptionDefault" . $id => "option_def",
                               "XrowFormElementOptionImageArray" . $id => "option_image",
                               "XrowFormElementOptionValueArray" . $id => "option_value" );

            // Read the formular config array
            foreach ( $keyArray as $tplFormKey => $dataKey )
            {
                $varName = $dataKey . "Array";
                $$varName = array();
                if ( $http->hasPostVariable( $tplFormKey ) )
                {
                    if( $dataKey != "desc" )
                    {
                        $$varName = self::strip($http->postVariable( $tplFormKey ));
                    }
                    else
                    {
                        $$varName = self::strip_allowable_tag($http->postVariable( $tplFormKey ));
                    }
                }
            }

            // Map the content
            foreach ( $formElementArray as $key )
            {
                $data = array();
                if ( isset( $typeArray[$key] ) )
                {
                    $type = $typeArray[$key];
                    $data['type'] = $type;
                    $data['name'] = $data['desc'] = $data['def'] = $data['min']= $data['max'] = $data['step'] = null;
                    $data['req'] = $data['val'] = $data['unique'] = false;
                    if ( isset( $nameArray[$key] ) )
                    {
                        $data['name'] = $nameArray[$key];
                    }
                    if ( isset( $crmclassArray[$key] ) )
                    {
                        $data['crmclass'] = $crmclassArray[$key];
                    }
                    if ( isset( $minArray[$key] ) )
                    {
                        $data['min'] = $minArray[$key];
                    }
                    if ( isset( $maxArray[$key] ) )
                    {
                        $data['max'] = $maxArray[$key];
                    }
                    if ( isset( $stepArray[$key] ) )
                    {
                        $data['step'] = $stepArray[$key];
                    }
                    if ( isset( $descArray[$key] ) )
                    {
                        $data['desc'] = $descArray[$key];
                    }
                    if ( isset( $reqArray[$key] ) )
                    {
                        $data['req'] = true;
                    }
                    if ( isset( $valArray[$key] ) )
                    {
                        $data['val'] = true;
                    }
                    if ( isset( $uniqueArray[$key] ) )
                    {
                        $data['unique'] = true;
                    }
                    if ( $type == 'checkbox' )
                    {
                        if ( isset( $defArray[$key] ) )
                        {
                            $data['def'] = true;
                        }
                        else
                        {
                            $data['def'] = false;
                        }
                    }
                    else
                    {
                        if ( isset( $defArray[$key] ) )
                        {
                            $data['def'] = $defArray[$key];
                        }
                    }
                    // check if this field is one from crm
                    $ini = eZINI::instance( 'xrowformgenerator.ini' );
                    if( strpos( $type, 'crmfield' ) !== false && $ini->hasVariable( 'Settings', 'UseCRM' ) && $ini->variable( 'Settings', 'UseCRM' ) == 'enabled' )
                    {
                        $pluginOptions = new ezpExtensionOptions( array( 'iniFile' => 'xrowformgenerator.ini',
                                                                         'iniSection' => 'PluginSettings',
                                                                         'iniVariable' => 'FormCRMPlugin' ) );
                        $pluginHandler = eZExtension::getHandlerClass( $pluginOptions );
                        if( !( $pluginHandler instanceof xrowFormCRM ) )
                        {
                            eZDebug::writeError( 'PluginHandler does not exist: ', __METHOD__ );
                        }
                        $crm = false;
                        if ( isset( $crmArray[$key] ) )
                            $crm = $crmArray[$key];
                        if ( isset( $option_typeArray[$key] ) && $option_typeArray[$key] != '' )
                            $data['option_type'] = $option_typeArray[$key];
                        $data = $pluginHandler->setAttributeDataForCRMField( $data, $http, $id, $crm );
                    }
                    if ( $type == 'options' or $type == 'imageoptions' )
                    {
                        if ( isset( $optionArray[$key] ) )
                        {
                            $data['option_type'] = $option_typeArray[$key];
                            $options = array();
                            foreach ( $optionArray[$key] as $optKey )
                            {
                                $item = array( 'name' => '', 'def' => false );
                                if ( $type == 'imageoptions' )
                                {
                                    $item['image'] = 0;
                                }
                                if ( isset( $option_nameArray[$key][$optKey] ) )
                                {
                                    $item['name'] = $option_nameArray[$key][$optKey];
                                }
                                if ( isset( $option_defArray[$key][$optKey] ) )
                                {
                                    $item['def'] = true;
                                }
                                if ( isset( $option_imageArray[$key][$optKey] ) )
                                {
                                    $item['image'] = $option_imageArray[$key][$optKey];
                                }
                                if ( isset( $option_valueArray[$key][$optKey] ) )
                                {
                                    $item['optional_value'] = $option_valueArray[$key][$optKey];
                                }
                                $options[$optKey] = $item;
                            }
                            $data['option_array'] = $options;
                        }
                    }
                }
                $data_array['form_elements'][] = $data;
            }
            $attribute->setAttribute( "data_text", serialize( $data_array ) );
            return true;
        }
        return false;
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $objectAttribute )
    {
        
        return $this->httpConfigInput( $http, $objectAttribute );
        
    }

    public static function fetchCollectionCountForObject( $objectID, $infoID )
    {
        if ( !is_numeric( $objectID ) )
        {
            return 1;
        }

        $db = eZDB::instance();
        $resultArray = $db->arrayQuery( "SELECT COUNT( * ) as count FROM ezinfocollection WHERE id != $infoID AND contentobject_id=$objectID" );

        return $resultArray[0]['count'] + 1;
    }

    /*!
     Fetches the http post variables for collected information
    */
    function fetchCollectionAttributeHTTPInput( $collection, $collectionAttribute, $http, $base, $objectAttribute )
    {
        $content = $objectAttribute->content();

        if ( $content['has_http_input'] == true and $content['has_error'] == false  )
        {    
            $amount = $this->fetchCollectionCountForObject( $collection->attribute( 'contentobject_id' ), $collection->attribute( 'id' ) );
            $content['no'] = $amount;

            $dataText = self::encryptData(serialize( $content ));
            $collectionAttribute->setAttribute( 'data_text', $dataText );

            // start an event
            try
            {
                ezpEvent::getInstance()->notify( 'formgenerator/export', array( $objectAttribute, $collection ) );
                ezpEvent::getInstance()->notify( 'formgeneratorxml/exportxml', array( $objectAttribute, $collectionAttribute ) );
                // Sending the mail
                $this->xrowSendFormMail( $collection, $collectionAttribute, $objectAttribute, $content );
                return true;
            }
            catch( Exception $e )
            {
                eZDebug::writeError( $e->getMessage(), 'xrowFormGeneratorType::fetchCollectionAttributeHTTPInput' );
            }
        }
        return false;
    }

    public function xrowSendFormMail( $collection, $collectionAttribute, $objectAttribute, $content )
    {
        $ini = eZINI::instance();
        $tpl = eZTemplate::factory();

        $object = $objectAttribute->attribute( 'object' );
        $tpl->setVariable( 'collection_id', $collection->attribute( 'id' ) );
        $tpl->setVariable( 'collection', $collection );
        $tpl->setVariable( 'object', $object );

        $tpl->setVariable( 'content', $content );

        if( $content['optin'] )
        {
            $info_collect_id = $collectionAttribute->InformationCollectionID;
            $hash = self::urlsafe_b64encode($info_collect_id);
            $actual_link = "http://$_SERVER[HTTP_HOST]/xrowform/optin/?id=".$hash;
            $tpl->setVariable( 'actual_link', $actual_link );
            $templateResult = $tpl->fetch( 'design:xrowformgenerator/xrowformmail_noaktive.tpl' );
            $subject = $tpl->variable( 'subject' );
            foreach ( $content['form_elements'] as $item )
            {
                if ( $item['type'] == 'email' and isset( $item['def'] ) and $item['def'] != '' )
                {
                    $receiverString = $item['def'];
                }
            }
        }
        else
        {
            $templateResult = $tpl->fetch( 'design:xrowformgenerator/xrowformmail.tpl' );
            $subject = $tpl->variable( 'subject' );
            $receiverString = $tpl->variable( 'email_receiver_xrow' );
        }
        $receiverArray = array();
        if( $receiverString != '' )
        {
            if( strpos( $receiverString, ';' ) !== false )
            {
                $receiverArray = explode( ";", $receiverString );
            }
            else
            {
                $receiverArray = array( $receiverString );
            }
            if ( count( $receiverArray ) == 0 )
            {
                $receiverArray = array( $ini->variable( "InformationCollectionSettings", "EmailReceiver" ) );
            }
        }

        if( is_array( $receiverArray ) )
        {
            $ccReceivers = false;
            $bccReceivers = false;
            $sender = false;
            if ($tpl->hasVariable( 'email_cc_receivers' ))
                $ccReceivers = $tpl->variable( 'email_cc_receivers' );
            if ($tpl->hasVariable( 'email_bcc_receivers' ))
                $bccReceivers = $tpl->variable( 'email_bcc_receivers' );
            if ($tpl->hasVariable( 'email_sender' ))
                $sender = $tpl->variable( 'email_sender' );
            if ( !self::validate( $sender ) )
            {
                if( $ini->hasVariable( 'MailSettings', 'EmailSender' ) )
                {
                    $sender = $ini->variable( 'MailSettings', 'EmailSender' );
                }
                else
                {
                    $sender = $ini->variable( 'MailSettings', 'AdminEmail' );
                }
            }

            ezcMailTools::setLineBreak( "\n" );
            $mail = new ezcMailComposer();
            $mail->charset = 'utf-8';
            $mail->from = new ezcMailAddress( $sender, '', $mail->charset );
            $mail->returnPath = $mail->from;
            foreach ( $receiverArray as $receiver )
            {
                $mail->addTo( new ezcMailAddress( $receiver, '', $mail->charset ) );
            }
            //  Handle CC recipients
            if( $ccReceivers )
            {
                if ( !is_array( $ccReceivers ) )
                    $ccReceivers = array( $ccReceivers );
                if ( count( $ccReceivers ) > 0 )
                {
                    foreach ( $ccReceivers as $ccReceiver )
                    {
                        if ( self::validate( $ccReceiver ) )
                        {
                            $mail->addCc( new ezcMailAddress( $ccReceiver, '', $mail->charset ) );
                        }
                    }
                }
            }
            // Handle BCC recipients
            if ( $bccReceivers )
            {
                if ( !is_array( $bccReceivers ) )
                    $bccReceivers = array( $bccReceivers );
                if ( count( $bccReceivers ) > 0 )
                {
                    foreach ( $bccReceivers as $bccReceiver )
                    {
                        if ( self::validate( $bccReceiver ) )
                        {
                            $mail->addBcc( new ezcMailAddress( $bccReceiver, '', $mail->charset ) );
                        }
                    }
                }
            }

            $mail->subject = $subject;
            $mail->subjectCharset = $mail->charset;
            $mail->plainText = $templateResult;
            foreach ( $content['form_elements'] as $item )
            {
                if ( $item['type'] == 'upload' and isset( $item['def'] ) and $item['def'] != '' )
                {
                    $binaryFile = eZHTTPFile::fetch( $item['def'] );
                    if ( $binaryFile instanceof eZHTTPFile )
                    {
                        $disposition = new ezcMailContentDispositionHeader();
                        $disposition->fileName =  $binaryFile->OriginalFilename;
                        $disposition->fileNameCharSet = 'utf-8'; // if using non-ascii characters in the file name
                        $disposition->disposition = 'attachment'; // default value is 'inline'
                        $mail->addAttachment( $binaryFile->Filename, null, null, null, $disposition );
                    }
                }
            }
            $mail->build();

            $mailsettings = array();
            $mailsettings["transport"] = $ini->variable( "MailSettings", "Transport" );
            $mailsettings["server"] = $ini->variable( "MailSettings", "TransportServer" );
            $mailsettings["port"] = $ini->variable( "MailSettings", "TransportPort" );
            $mailsettings["user"] = $ini->variable( "MailSettings", "TransportUser" );
            $mailsettings["password"] = $ini->variable( "MailSettings", "TransportPassword" );
            $mailsettings["connectionType"] = $ini->variable( 'MailSettings', 'TransportConnectionType' );

            if( trim($mailsettings["port"]) == "" )
            {
                $mailsettings["port"] = null;
            }
            if ( strtolower($mailsettings["transport"]) == "smtp" )
            {
                $options = new ezcMailSmtpTransportOptions();
                if( trim($mailsettings["password"]) === "" )
                {
                    $transport = new ezcMailSmtpTransport( $mailsettings["server"], "", "", $mailsettings["port"], $options );
                }
                else
               {
                    $options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_AUTO;
                    $transport = new ezcMailSmtpTransport( $mailsettings["server"], $mailsettings["user"], $mailsettings["password"], $mailsettings["port"], $options );
                }
            }
            else if ( strtolower($mailsettings["transport"]) == "sendmail" )
            {
                $transport = new ezcMailMtaTransport();
            }
            else if ( strtolower($mailsettings["transport"]) == "file" )
            {
                //do own mail creation because ezc mail provides no file sending
                $mail = new eZMail();
                $mail->setSender( $sender );
                foreach ( $receiverArray as $receiver )
                {
                    $mail->addReceiver( $receiver );
                }
                $mail->setSubject( $subject );
                $mail->setBody( $templateResult );
                eZFileTransport::send( $mail );
            }
            else
            {
                eZDebug::writeError( "Wrong Transport in MailSettings in", 'xrowFormGeneratorType::xrowSendFormMail' );
                return null;
            }

            //ezcmail sending
            if ( strtolower($mailsettings["transport"]) != "file" )
            {
                try
                {
                    $transport->send( $mail );
                }
                catch ( ezcMailTransportException $e )
                {
                    eZDebug::writeError( $e->getMessage(), 'xrowFormGeneratorType::xrowSendFormMail' );
                }
            }
        }
    }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $id = $contentObjectAttribute->attribute( 'id' );
        $contentobject_id = $contentObjectAttribute->attribute( 'contentobject_id' );

        $cacheKey = 'col';
        if ( $contentObjectAttribute instanceof eZContentObjectAttribute )
        {
            $cacheKey = 'attr';
        }

        if ( isset( $GLOBALS['XrowFormCache'][$id][$cacheKey] ) )
        {
            return $GLOBALS['XrowFormCache'][$id][$cacheKey];
        }

        $trans = eZCharTransform::instance();

        if(! unserialize($contentObjectAttribute->attribute( "data_text" )))
        {
            $raw_content = self::decryptData($contentObjectAttribute->attribute( "data_text" ));
        }else{
            $raw_content = $contentObjectAttribute->attribute( "data_text" );
        }
        
        $content = array( 'form_elements' => array(),
                          'use_captcha' => false,
                          'optin'=>false,
                          'show_amount' => false,
                          'show_anzeige'=> false,
                          'json' => json_encode( array() ),
                          'has_error' => false,
                          'error_array' => array(),
                          'has_http_input' => false,
                          'no' => 1 );

        // Checking if there is http input for the form fields
        $httpInput = false;
        $httpKey =  "XrowFormInputArray";
        $http = eZHTTPTool::instance();
        if ( $http->hasPostVariable( $httpKey ) )
        {
            $inputAllKeyArray = self::strip( $http->postVariable( $httpKey ) );
            $inputKeyArray = $inputAllKeyArray[$id];
            if ( count( $inputKeyArray ) > 0 )
            {
                $httpInput = true;
                $content['has_http_input'] = true;
                $inputAllArray = array();
                $inputArray = array();
                $inputAllTypeArray = array();
                $inputTypeArray = array();
                if( $http->hasPostVariable( 'XrowFormInput' ) )
                {
                    $inputAllArray = self::strip( $http->postVariable( 'XrowFormInput' ) );
                    if( isset( $inputAllArray[$id] ) )
                    {
                        $inputArray = $inputAllArray[$id];
                    }
                }
                if( $http->hasPostVariable( 'XrowFormInputType' ) )
                {
                    $inputAllTypeArray = self::strip( $http->postVariable( 'XrowFormInputType' ) );
                    if( isset( $inputAllTypeArray[$id] ) )
                    {
                        $inputTypeArray = $inputAllTypeArray[$id];
                    }
                }
                if ( $http->hasPostVariable( 'XrowFormInputCRM' ) )
                {
                    $inputAllCRMArray = self::strip( $http->postVariable( 'XrowFormInputCRM' ) );
                    if( isset( $inputAllCRMArray[$id] ) )
                    {
                        $inputCRMArray = $inputAllCRMArray[$id];
                    }
                }
                if ( $http->hasPostVariable( 'XrowFormInputTypeCRM' ) )
                {
                    $inputAllCRMFieldsArray = self::strip( $http->postVariable( 'XrowFormInputTypeCRM' ) );
                    if( isset( $inputAllCRMFieldsArray[$id] ) )
                    {
                        $inputCRMFieldsArray = $inputAllCRMFieldsArray[$id];
                    }
                }
            }
        }
        #die(var_dump($inputCRMFieldsArray));
        if ( strlen( $raw_content ) > 0 )
        {
            $content = unserialize( $raw_content );
            $content['has_http_input'] = false;
            $content['error_array'] = array();
            $content['has_error'] = false;
            $content['has_http_input'] = $httpInput;
            $content['json'] = json_encode( array() );
            $usedIDArray = array();
            $locale = eZLocale::instance();
            foreach ( $content['form_elements'] as $key => $item )
            {
                $content['form_elements'][$key]['error'] = false;
                $content['form_elements'][$key]['has_input'] = false;
                if ( trim( $item['name'] ) == "" )
                {
                    $item['name'] = $key;
                }
                $i = 0;
                $elementID = mb_strtolower( $trans->transformByGroup( $item['name'], 'identifier' ) );
                $testID = $elementID;
                do
                {
                    if ( !in_array( $testID, $usedIDArray ) )
                    {
                        $usedIDArray[] = $testID;
                        $content['form_elements'][$key]['class'] = "xrow-form-" .  mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) );
                        $content['form_elements'][$key]['id'] = $testID;
                        break;
                    }
                    $testID = $elementID . '-' . $i;
                    $i++;
                } while ( $i < 100 );

                // checking if there is any http input for the current element
                // the http input overrides the default values
                if ( $httpInput )
                {
                    if( isset( $inputKeyArray[$key] ) )
                    {
                        $elKey = $inputKeyArray[$key];
                        // check if this field is a from crm
                        $ini = eZINI::instance( 'xrowformgenerator.ini' );
                        if( strpos( $item['type'], 'crmfield' ) !== false && $ini->hasVariable( 'Settings', 'UseCRM' ) && $ini->variable( 'Settings', 'UseCRM' ) == 'enabled' )
                        {
                            $pluginOptions = new ezpExtensionOptions( array( 'iniFile' => 'xrowformgenerator.ini',
                                                                             'iniSection' => 'PluginSettings',
                                                                             'iniVariable' => 'FormCRMPlugin' ) );
                            $pluginHandler = eZExtension::getHandlerClass( $pluginOptions );
                            if( !( $pluginHandler instanceof xrowFormCRM ) )
                            {
                                eZDebug::writeError( 'PluginHandler does not exist: ', __METHOD__ );
                            }
                            if( isset( $inputCRMArray ) && isset( $inputCRMArray[$elKey] ) )
                            {
                                $inputContentCollection = $inputCRMArray[$elKey];
                                $content = $pluginHandler->setAttributeDataForCollectCRMField( $content, $key, $item, $inputContentCollection, $contentobject_id, $trans, $inputCRMFieldsArray[$elKey] );
                            }
                            elseif( isset( $inputCRMFieldsArray ) && isset( $inputCRMFieldsArray[$elKey] ) ) // if it is a checkbox
                            {
                                $inputContentCollection = $inputCRMFieldsArray[$elKey];
                                $content = $pluginHandler->setAttributeDataForCollectCRMField( $content, $key, $item, $inputContentCollection, $contentobject_id, $trans, $inputCRMFieldsArray[$elKey] );
                            }
                        }
                        if ( $item['type'] == $inputTypeArray[$elKey] )
                        {
                            switch ( $item['type'] )
                            {
                                case "text":
                                case "string":
                                case "country":
                                {
                                    $data = '';
                                    if ( isset( $inputArray[$elKey] ) )
                                    {
                                        $data = trim( $inputArray[$elKey] );
                                    }
                                    if ( $item['req'] == true && $data == '' )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Input required." );
                                    }
                                    $content['form_elements'][$key]['def'] = $data;
                                }break;

                                case "email":
                                {
                                    $data = '';
                                    if ( isset( $inputArray[$elKey] ) )
                                    {
                                        $data = trim( $inputArray[$elKey] );
                                    }

                                    if ( $item['req'] == true && $data == '' )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Input required." );
                                    }
                                    elseif( $item['unique'] == true && !self::email_unique( $data, $contentobject_id ) )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Your email was already submitted to us. You can't use the form twice." );
                                    }
                                    elseif( $item['val'] == true && !self::validate( $data ) )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Email address is not valid." );
                                    }

                                    $content['form_elements'][$key]['def'] = $data;
                                }break;

                                case "checkbox":
                                {
                                    $data = false;
                                    if ( isset( $inputArray[$elKey] ) )
                                    {
                                        $data = true;
                                    }
                                    if ( $item['req'] == true && !$data )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "You need to select this checkbox." );
                                    }
                                    $content['form_elements'][$key]['def'] = $data;
                                }break;

                                case "telephonenumber":
                                {
                                    $data = '';
                                    $number = '';
                                    $checkTelephone = false;
                                    if ( isset( $inputArray[$elKey] ) )
                                    {
                                        if( is_string( $inputArray[$elKey] ) )
                                        {
                                            $data = trim( $inputArray[$elKey] );
                                        }
                                    }
                                    if ( $item['req'] == true && $data == '' )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Input required." );
                                    }
                                    elseif( $item['val'] == true )
                                    {
                                        $validate = self::telephone_validate( $data );
                                        if( !$validate['validateStatus'] || strlen( $data ) >= 25 )
                                        {
                                            $content['form_elements'][$key]['error'] = true;
                                            $content['has_error'] = true;
                                            $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Please enter a valid phone number. Example" ) . ' ' . $validate['formatExample'] . '.' ;
                                        }
                                    }
                                    $content['form_elements'][$key]['def'] = $data;
                                }break;

                                case "number":
                                {
                                    $data = '';
                                    if ( isset( $inputArray[$elKey] ) )
                                    {
                                        $data = trim( $inputArray[$elKey] );
                                        $data = str_replace(" ", "", $data );
                                    }
                                    if ( $item['req'] == true && $data == '' )
                                    {
                                        $content['form_elements'][$key]['error'] = true;
                                        $content['has_error'] = true;
                                        $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Input required." );
                                    }
                                    elseif ( $item['val'] == true )
                                    {
                                        $dataTest = $locale->internalNumber( $data );
                                        $floatValidator = new eZFloatValidator();
                                        $state = $floatValidator->validate( $dataTest );
                                        if ( $state !== 1 )
                                        {
                                            $content['form_elements'][$key]['error'] = true;
                                            $content['has_error'] = true;
                                            $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Please enter a valid number." );
                                        }
                                    }
                                    $content['form_elements'][$key]['def'] = $data;
                                }break;

                                case "options":
                                case "imageoptions":
                                {
                                    if ( isset( $inputArray[$elKey] ) )
                                    {
                                        if (strpos($inputArray[$elKey], '|')) {
                                            $valueExplode = explode('|', $inputArray[$elKey]);
                                            $inputArray[$elKey] = $optionKey = $valueExplode[1];
                                            $optionValue = $valueExplode[0];
                                        }
                                        $dataArray = $inputArray[$elKey];
                                        if ( !is_array( $dataArray ) )
                                        {
                                            $dataArray = array( $dataArray );
                                        }

                                        $optSelected = false;
                                        if( isset( $item['option_array'] ) )
                                        {
                                            foreach ( $item['option_array'] as $optKey => $optItem )
                                            {
                                                $content['form_elements'][$key]['option_array'][$optKey]['def'] = false;
                                                if ( in_array( $optItem['name'], $dataArray ) || (isset($optionKey) && $optKey == $optionKey))
                                                {
                                                    $content['form_elements'][$key]['option_array'][$optKey]['def'] = true;
                                                    if (isset($optionKey) && $optKey == $optionKey)
                                                        $content['form_elements'][$key]['option_array'][$optKey]['value'] = $optionValue;
                                                    $optSelected = true;
                                                }
                                            }
                                        }
                                        if ( $item['req'] == true )
                                        {
                                            if ( !$optSelected )
                                            {
                                                $content['form_elements'][$key]['error'] = true;
                                                $content['has_error'] = true;
                                                $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Please select at least one option." );
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if ( $item['req'] == true )
                                        {
                                            $content['form_elements'][$key]['error'] = true;
                                            $content['has_error'] = true;
                                            $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Please select at least one option." );
                                        }
                                    }

                                }break;
                                case "upload":
                                {
                                    $test = new eZBinaryFileType();
                                    $test->checkFileUploads();
                                    $fileKey = "XrowFormInputFile_" . $id . '_' . $key;
                                    $maxSize = 1024 * 1024 * 50;
                                    $checkUpload = eZHTTPFile::canFetch( $fileKey, $maxSize );
                                    if ( $checkUpload >= 0 )
                                    {
                                        $binaryFile = eZHTTPFile::fetch( $fileKey );

                                        if ( $binaryFile instanceof eZHTTPFile )
                                        {
                                            $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
                                            $version = $contentObjectAttribute->attribute( "version" );
                                            
                                            $mimeData = eZMimeType::findByFileContents( $binaryFile->attribute( "original_filename" ) );
                                            $mime = $mimeData['name'];
                                            
                                            if ( $mime == '' )
                                            {
                                                $mime = $binaryFile->attribute( "mime_type" );
                                            }
                                            
                                            $extension = eZFile::suffix( $binaryFile->attribute( "original_filename" ) );
                                            $binaryFile->setMimeType( $mime );
                                            if ( !$binaryFile->store( "original", $extension ) )
                                            {
                                                eZDebug::writeError( "Failed to store http-file: " . $binaryFile->attribute( "original_filename" ),
                                                "EnhancedeZBinaryFileType" );
                                                return false;
                                            }
                                            
                                            $binary = eZBinaryFile::fetch( $contentObjectAttributeID, $version );
                                            if ( $binary === null )
                                                $binary = eZBinaryFile::create( $contentObjectAttributeID, $version );
                                            
                                            $orig_dir = $binaryFile->storageDir( "original" );
                                            
                                            $binary->setAttribute( "contentobject_attribute_id", $contentObjectAttributeID );
                                            $binary->setAttribute( "version", $version );
                                            $binary->setAttribute( "filename", basename( $binaryFile->attribute( "filename" ) ) );
                                            $binary->setAttribute( "original_filename", $binaryFile->attribute( "original_filename" ) );
                                            $binary->setAttribute( "mime_type", $mime );
                                            $binary->store();
                                            $filepath=$binary->filePath();
                                            $content['form_elements'][$key]['def'] = $fileKey;
                                            $content['form_elements'][$key]['file_path'] = $filepath;
                                            $content['form_elements'][$key]['original_filename'] = $binaryFile->attribute( 'original_filename' );
                                        }
                                    }
                                    else
                                    {
                                        if ( $item['req'] == true AND $checkUpload == eZHTTPFile::UPLOADEDFILE_DOES_NOT_EXIST )
                                        {
                                           $content['form_elements'][$key]['error'] = true;
                                           $content['has_error'] = true;
                                           $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "Please add a valid file for upload." );
                                        }
                                        else if ( $checkUpload == eZHTTPFile::UPLOADEDFILE_EXCEEDS_PHP_LIMIT or
                                                  $checkUpload == eZHTTPFile::UPLOADEDFILE_EXCEEDS_MAX_SIZE )
                                        {
                                            $content['form_elements'][$key]['error'] = true;
                                            $content['has_error'] = true;
                                            $content['error_array'][mb_strtolower( $trans->transformByGroup( $item['name'], 'urlalias' ) )] = $item['name'] . ": " . ezpI18n::tr( 'kernel/classes/datatypes', "The uploaded file is too big. Please select a smaller one." );
                                        }
                                        else
                                        {
                                            eZDebug::writeError( 'Unknown file upload error', __METHOD__ );
                                        }
                                    }
                                }break;
                                default:
                                {
                                    #no validation needed
                                }break;
                            }
                        }
                        else
                        {
                            eZDebug::writeError( 'xrowForm input error: Wrong type of element.', __METHOD__ );
                        }
                    }
                } /* if $http_input */

                if ( $item['type'] == 'imageoptions' )
                {
                    if( isset( $item['option_array'] ) )
                    {
                        foreach ( $item['option_array'] as $optKey => $optItem )
                        {
                            if ( isset( $content['form_elements'][$key]['option_array'][$optKey]['image'] )
                                 AND $content['form_elements'][$key]['option_array'][$optKey]['image'] > 0 )
                            {
                                self::getImageForOption( $content['form_elements'][$key]['option_array'][$optKey], $id );
                            }
                        }
                    }
                }

                // json encode for javascript
                $content['form_elements'][$key]['json'] = json_encode( $content['form_elements'][$key] );
                $content['data_map'][$testID] = $content['form_elements'][$key];
            }
        }
        // Checking the captcha code
        $ini = eZINI::instance('xrowformgenerator.ini');
        if( $ini->variable( "Settings", "Captcha" ) == "recaptcha")
        {
            if ( $httpInput and $content['use_captcha'] )
            {
                $dataType = new recaptchaType();
                $spamTest = $dataType->validateCollectionAttributeHTTPInput( $http, 'ContentObjectAttribute', $contentObjectAttribute );
                if( $spamTest == eZInputValidator::STATE_INVALID )
                {
                    $content['has_error'] = true;
                    $content['error_array']['recaptcha'] = ezpI18n::tr( 'kernel/classes/datatypes', "The reCAPTCHA wasn't entered correctly. Please try again." );
                }
            }
        }
        elseif( $ini->variable( "Settings", "Captcha" ) == "humancaptcha")
        {
            if ( $httpInput and $content['use_captcha'] )
            {
                $dataType = new eZHumanCAPTCHAType();
                $spamTest = $dataType->validateObjectAttributeHTTPInput( $http, 'ContentObjectAttribute', $contentObjectAttribute );

                if ( $spamTest !== eZInputValidator::STATE_ACCEPTED )
                {
                    $content['has_error'] = true;
                    $content['error_array']['humancaptcha'] = ezpI18n::tr( 'kernel/classes/datatypes', "Spam protection. The signs of the captcha code didn't match. Please enter the correct code of the image at the bottom." );
                }
            }
        }
        $GLOBALS['XrowFormCache'][$id][$cacheKey] = $content;
        #echo "<pre>";
        #var_dump($content);
        #echo "</pre>";
        #die();
        return $content;
    }

    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        $action = explode( "_", $action );
        if ( count( $action ) == 3 )
        {
            $row = $action[0];
            $item = $action[1];
            $action = $action[2];
        }

        switch ( $action )
        {
            case "selectimage" :
            {
                if ( $http->hasPostVariable( 'BrowseActionName' ) and
                          $http->postVariable( 'BrowseActionName' ) == 'xrowFormImageBrowse'
                          and $http->hasPostVariable( "SelectedNodeIDArray" ) )
                {
                    if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
                    {
                        $selectedNodeIDArray = $http->postVariable( "SelectedNodeIDArray" );
                        $nodeID = $selectedNodeIDArray[0];
                        $dataText = $contentObjectAttribute->attribute( "data_text" );
                        $content = unserialize( $dataText );
                        #eZDebug::writeDebug( $content, 'content' );
                        if ( isset( $content['form_elements'] ) )
                        {
                            $content['form_elements'][$row]['option_array'][$item]['image'] = $nodeID;

                            if ( $nodeID > 0 )
                            {
                                self::getImageForOption( $content['form_elements'][$row]['option_array'][$item], $contentObjectAttribute->attribute( 'id' ) );
                            }
                            $contentObjectAttribute->setAttribute( "data_text", serialize( $content ) );
                            $contentObjectAttribute->store();
                            
                        }
                    }
                }
            } break;

            case "browse" :
            {
                $module = $parameters['module'];
                $redirectionURI = $parameters['current-redirection-uri'];

                eZContentBrowse::browse( array( 'action_name' => 'xrowFormImageBrowse',
                                                'browse_custom_action' => array( 'name' => 'CustomActionButton[' . $contentObjectAttribute->attribute( 'id' ) . '_' . $row . '_' . $item . '_selectimage]',
                                                                                 'value' => '' ),
                                                'persistent_data' => array( 'HasObjectInput' => 0 ),
                                                'from_page' => $redirectionURI ),
                                         $module );
            } break;

            default :
            {
                eZDebug::writeError( "Unknown custom HTTP action: " . $action, __METHOD__ );
            } break;
        }
    }

    /**
     * Returns the image url of an image option
     *
     * @param array $imgArray
     * @param int $attributeID
     * @return mixed
     */
    public static function getImageForOption( array &$imgArray, $attributeID )
    {
        $result = array();
        $nodeID = $imgArray['image'];
        $iNode = eZContentObjectTreeNode::fetch( $nodeID );
        if ( $iNode instanceof eZContentObjectTreeNode )
        {
            $dataMap = $iNode->attribute( 'data_map' );
            foreach ( $dataMap as $dataItem )
            {
                if ( $dataItem->attribute( 'data_type_string' ) == 'ezimage'
                     AND $dataItem->hasContent() )
                {
                    // image attribute found
                    $image = $dataItem->content();
                    $imageVariant = $image->attribute( 'small' );
                    if ( is_array( $imageVariant ) and count( $imageVariant ) > 0 )
                    {
                        $imageURL = $imageVariant['url'];
                        $imgArray['image_src'] = '/' . $imageURL;
                        $imgArray['attribute_id'] = $attributeID;
                        $imgArray['width'] = $imageVariant['width'];
                        $imgArray['height'] = $imageVariant['height'];
                        return;
                    }
                }
            }
        }
        eZDebug::writeDebug( 'Invalid node id', __METHOD__ );
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        if ( count( $content['form_elements'] ) > 0 )
        {
            return true;
        }
        return false;
    }

    /*!
     \reimp
    */
    function isInformationCollector()
    {
        return true;
    }

    /*!
     \return true if the data type can do information collection
    */
    function hasInformationCollection()
    {
        return true;
    }

    /**
     * Exports all collected form data
     *
     * @param int $attributeID
     * @param int $version
     * @param int $offset
     * @param int $limit
     * @return string
     */
    public function exportFormData( $attributeID, $version, $offset, $limit = 1000 )
    {
        $this->separationChar = chr( 9 );
        $attribute = eZContentObjectAttribute::fetch( $attributeID, $version );
        if ( !( $attribute instanceof eZContentObjectAttribute ) )
        {
            eZDebug::writeError( $attribute . " attribute id not found ", __METHOD__ );
            return "";
        }
        $content = $attribute->content();

        $db = eZDB::instance();
        $sql = "SELECT
                   a.data_text,
                   b.id,
                   b.created
                FROM
                   ezinfocollection_attribute a,
                   ezinfocollection b
                WHERE
                   a.contentobject_attribute_id='$attributeID'
                   AND a.informationcollection_id = b.id
                ORDER BY b.id";

        $resultArray = $db->arrayQuery( $sql, array( 'offset' => $offset, 'limit' => $limit ) );

        $first = true;

        $contentArray = array();
        $i = 0;
        $contentArray[$i]['no'] = ezpI18n::tr( 'xrowformgenerator/mail', "No.:" );
        $contentArray[$i]['created_xrow'] = ezpI18n::tr( 'xrowformgenerator/mail', "Date" );
        foreach ( $content['form_elements'] as $fItem )
        {
            if ( trim( $fItem['name'] ) == '' )
            {
                $contentArray[0][$fItem['id']] = $fItem['id']; 
            }
            else 
            {
                $contentArray[0][$fItem['id']] = $fItem['name']; 
            } 
        }

        $keyArray = array_keys( $contentArray[0] );

        $i++;
        if ( count( $resultArray ) > 0 )
        {
            foreach ( $resultArray as $item )
            {
                $formData = unserialize( $item['data_text'] );

                if ( count( $formData['form_elements'] ) > 0 )
                {
                    if ( isset( $formData['no'] ) )
                    {
                        $contentArray[$i]['no'] = $formData['no'];
                    }
                    else 
                    {
                        $contentArray[$i]['no'] = $i;
                    }
                    
                    $contentArray[$i]['created_xrow'] = date( "d.m.Y H:i:s", $item['created'] );
                    foreach ( $formData['form_elements'] as $aItem )
                    {
                        /**
                         * If the key isn't present in the definition, add the data
                         * at the end
                         */
                        if ( !in_array( $aItem['id'], $keyArray ) )
                        {
                            $keyArray[] = $aItem['id'];
                            if ( trim( $aItem['name'] ) == '' )
                            {
                                $contentArray[0][$aItem['id']] = $aItem['id']; 
                            }
                            else 
                            {
                                $contentArray[0][$aItem['id']] = $aItem['name']; 
                            }                              
                        }
                        switch ( $aItem['type'] )
                        {
                            case 'checkbox':
                            {
                                if ( $aItem['def'] )
                                {
                                    $contentArray[$i][$aItem['id']] = ezpI18n::tr( 'xrowformgenerator/mail', "Yes" );
                                }
                                else
                                {
                                    $contentArray[$i][$aItem['id']] = ezpI18n::tr( 'xrowformgenerator/mail', "No" );
                                }
                            }break;
                            case "options":
                            case "imageoptions":
                            {
                                $oFirst = true;
                                foreach ( $aItem['option_array'] as $optItem )
                                {
                                    if ( $optItem['def'] )
                                    {
                                        if ( $oFirst )
                                        {
                                            $oFirst = false;
                                        }
                                        else
                                        {
                                            $contentArray[$i][$aItem['id']] .= ', ';
                                        }
                                        $contentArray[$i][$aItem['id']] .= trim( $optItem['name'] );
                                    }
                                }
                            }break;
                            default:
                            {
                                $contentArray[$i][$aItem['id']] = trim( $aItem['def'] );
                            }break;
                        }
                    }

                    $i++;
                }
            }
        }

        $export = "";
        
        foreach ( $contentArray as $no => $line )
        {
            $first = true;
            foreach ( $keyArray as $key )
            {
                if ( isset( $line[$key] ) )
                {
                    $entry = $line[$key];
                }
                else 
                {
                    $entry = "";
                }
                if ( $first )
                {
                    $first = false;
                }
                else
                {
                    $export .= $this->separationChar;
                }
                $export .= $this->escape( str_replace( chr( 9 ), ' ', $entry ) );
            }
            $export .= $this->lineEnding;
        }

        return $export;
    }

    /**
     * Escapes a string for csv export
     *
     * @param string $stringtoescape
     * @return string
     */
    public function escape( $stringtoescape )
    {
        //ASCII/CRLF=0x0D 0x0A   13 10

        if ( $this->escape and ( strpos( $stringtoescape, $this->encloseChar ) !== false or
             strpos( $stringtoescape, $this->separationChar ) !== false or
             strpos( $stringtoescape, chr(13)) !== false or // CR
             strpos( $stringtoescape, chr(10)) !== false )    // LF
           )
        {
           $stringtoescape = str_replace( $this->encloseChar, $this->encloseChar . $this->encloseChar, $stringtoescape );
           return $this->encloseChar . $stringtoescape . $this->encloseChar;
        }
        else
        {
            $stringtoescape = str_replace( chr(13), '', $stringtoescape );
            $stringtoescape = str_replace( chr(10), '', $stringtoescape );
            return $stringtoescape;
        }
    }

    static function telephone_validate( $number )
    {
        $formatExample = '+49 30 123456';
        $ini = eZINI::instance('xrowformgenerator.ini');
        if( $ini->hasVariable( "Settings", "TelephoneNumberPattern" ) && $ini->variable( "Settings", "TelephoneNumberPattern" ) != '' )
        {
            $pattern = $ini->variable( "Settings", "TelephoneNumberPattern" );
        }
        else
        {
            $areaCodes = $ini->variable( "Settings", "TelephoneAreaCodes" );
            $defaultPattern = $ini->variable( "Settings", "TelephoneDefaultPattern" );
            $pattern = '/^\+(' . $areaCodes . ')[ |]?' . $defaultPattern . '/';
        }
        if( $ini->hasVariable( "Settings", "TelephoneNumberExample" ) && $ini->variable( "Settings", "TelephoneNumberExample" ) != '' )
        {
            $formatExample = $ini->variable( "Settings", "TelephoneNumberExample" );
        }
        return array( 'formatExample' => $formatExample, 'validateStatus' => preg_match( $pattern, $number ) );
    }

    /*!
      \static
      Static function for validating email addresses.

      Returns true if successful, false if not.
    */
    static function validate( $address )
    {
        if ( $address === false || trim($address) == '' ) {
            return false;
        }
        $mxhosts = array();
        $hosts = array();
        $disposable_ip = array();
        $ini = eZINI::instance( 'blacklist.ini' );
        $blacklist = $ini->variable( 'SetBlackList', 'DomainsList' );
        foreach( $blacklist as $domain_disposable ) {
            if( getmxrr( $domain_disposable, $hosts ) ){
                foreach( $hosts as $host ) {
                    $disposable_ip[] = gethostbyname( $host );
                }
            }
        }
        $ini = eZINI::instance('xrowformgenerator.ini');
        if( $ini->hasVariable( "Settings", "InputEmail" ) && $ini->variable( "Settings", "InputEmail" ) != '' ) {
            $regexp = $ini->variable( "Settings", "InputEmail" );
        }
        else if( $ini->hasVariable( "Settings", "PHPInputEmail" ) && $ini->variable( "Settings", "PHPInputEmail" ) != '' )
        {
            $regexp = $ini->variable( "Settings", "PHPInputEmail" );
        }
        else if( $ini->hasVariable( "Settings", "PHPEmailDefaultPattern" ) && $ini->variable( "Settings", "PHPEmailDefaultPattern" ) != '' )
        {
            $regexp = $ini->variable( "Settings", "PHPEmailDefaultPattern" );
        }
        else
        {
            $regexp = $ini->variable( "Settings", "EmailDefaultPattern" );
        }
        if( !preg_match( $regexp, $address ) ) {
            return false;
        }
        else {
            list( $alias, $domain ) = explode( "@", $address );
            if ( !checkdnsrr( $domain, "MX" ) ) {
                return false;
            }
            else {
                if( !getmxrr( $domain, $mxhosts ) ) {
                    return false;
                }
                else {
                    foreach( $mxhosts as $host ) {
                        if( in_array( gethostbyname( $host ), $disposable_ip ) )
                            return false;
                    }
                    return true;
                }
            }
        }
    }

    static function email_unique( $address, $contentobject_id )
    {
        if ( trim($address) == '' ) {
            return false;
        }
        // gets DB instance
        $db = eZDB::instance();

        $sql = "
           SELECT ezinfocollection_attribute.data_text
           FROM 
              ezinfocollection_attribute
           WHERE 
              contentobject_id = '$contentobject_id'";

        // executes query
        $resArray = $db->arrayQuery( $sql );
        if( count( $resArray ) > 0 )
        {
            foreach( $resArray as $resItem )
            {
                if(! unserialize($resItem["data_text"]))
                {
                    $resItemArray = unserialize(self::decryptData($resItem["data_text"]));
                }else{
                    $resItemArray = unserialize( $resItem["data_text"] );
                }
               
                if( is_array( $resItemArray ) && count( $resItemArray ) > 0 )
                {
                    foreach( $resItemArray as $resItemArrayItems )
                    {
                        if(is_array($resItemArrayItems))
                        {
                            foreach($resItemArrayItems as $resItemArrayItem)
                            {
                                if( in_array( $address,$resItemArrayItem,true ))
                                {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    static function encryptData( $planeTextData )
    {
       //generate a new key(standby).
       /*$sys = eZSys::instance();
        $storage_dir = $sys->rootDir()."/settings/override/";
       if (!is_file($storage_dir.'xrow-formular-private.key') && !is_file($storage_dir.'xrow-formular-public.key'))
        {
            $config = array('private_key_bits' => 2048,
                            'private_key_type' => OPENSSL_KEYTYPE_RSA);
            $privateKey = openssl_pkey_new($config);
            openssl_pkey_export_to_file($privateKey, $storage_dir.'xrow-formular-private.key');
            $key = openssl_pkey_get_details($privateKey);
            file_put_contents($storage_dir.'xrow-formular-public.key', $key['key']);
            openssl_free_key($privateKey);
        }*/
        if ( $planeTextData != '' )
        {
            $xrowini = eZINI::instance( 'xrowformgenerator.ini' );
            $publicKey_array = $xrowini->variable( 'EncryptionSettings', 'PublicKey' );
            $publicKey = join("\n",$publicKey_array);
            $planeText = gzcompress($planeTextData);
            $getPublicKey = openssl_pkey_get_public($publicKey);
            $p_key = openssl_pkey_get_details($getPublicKey);
            $chunkSize = ceil($p_key['bits'] / 8) - 11;
            $output = '';
            while ($planeText) {
                $chunk = substr($planeText, 0, $chunkSize);
                $planeText = substr($planeText, $chunkSize);
                $encrypted = '';
                if ( !openssl_public_encrypt($chunk, $encrypted, $getPublicKey)) {
                    eZDebug::writeError( "failed to encrypt data!" );
                    return false;
                }
                $output .= $encrypted;
            }
            openssl_free_key($getPublicKey);
            $output = base64_encode($output);
            return $output;
        }
    }

    static function decryptData( $encryptedStringData, $limit = 0 )
    {
        $xrowini = eZINI::instance( 'xrowformgenerator.ini' );
        $keyStr_array = $xrowini->variable( 'EncryptionSettings', 'PrivateKey' );
        $keyStr = join("\n",$keyStr_array);
        if (!$privateKey = openssl_pkey_get_private($keyStr)) {
             eZDebug::writeError( "get private key failed!" );
             return false;
        }
        if ( $encryptedStringData != '')
        {
            $encrypted = base64_decode($encryptedStringData);
        }
        
        $p_key = openssl_pkey_get_details($privateKey);
        $chunkSize = ceil($p_key['bits'] / 8);
        $output = '';
        
        while ($encrypted) {
            $chunk = substr($encrypted, 0, $chunkSize);
            $encrypted = substr($encrypted, $chunkSize);
            $decryptd = '';
            if (!openssl_private_decrypt($chunk, $decryptd, $privateKey)) {
                eZDebug::writeError("failed to decrypt data!");
                return false;
            }
            $output .= $decryptd;
        }
        openssl_free_key($privateKey);
        $output = gzuncompress($output);
        
        // cut the result, if a limit is greater than 0
        if ( $limit > 0 )
        {
            $output = strrev( substr( strrev( $output ), 0, 4 ) );
        }
        return $output;
    }
    
    static function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }
    
    static function urlsafe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4)
        {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
    
    public $escape = true;
    public $encloseChar = '"';
    public $separationChar = ";";
    public $lineEnding = "\r\n";

}

eZDataType::register( xrowFormGeneratorType::DATA_TYPE_STRING, "xrowFormGeneratorType" );
