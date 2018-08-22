<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Elca\Service;
use Beibob\Blibs\Environment;
use Exception;
use PHPMailer;

/**
 * Mail service
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 *
 */
class Mailer
{
    /**
     * @var
     */
    protected $subject;

    /**
     * @var
     */
    protected $htmlContent;

    /**
     * @var
     */
    protected $textContent;

    /**
     * @var array
     */
    protected $attachments = array();

    /**
     * @var mixed
     */
    protected $Config;

    /**
     *
     * @param Config $Config
     */
    public function __construct($subject = null, $htmlContent = null, $textContent = null, $Config = null)
    {
        $this->setSubject($subject);

        if (!is_null($htmlContent))
            $this->setHtmlContent($htmlContent);

        if (!is_null($textContent))
            $this->setTextContent($textContent);

        if (!is_null($Config))
            $this->Config = $Config;
        else
        {
            $Config = Environment::getInstance()->getConfig();
            $this->Config = $Config->phpMailer;
        }
    }
    // __construct


    /**
     * Sets the html content
     *
     * @param string $htmlContent
     */
    public function setHtmlContent($htmlContent)
    {
        $this->htmlContent = $htmlContent;
    }
    // End setHtmlContent


    /**
     * Sets the textContent
     *
     * @param string $textContent
     */
    public function setTextContent($textContent)
    {
        $this->textContent = $textContent;
    }
    // End setTextContent


    /**
     * 
     * @param type $path
     */
    public function addAttachment($path)
    {
        $this->attachments[$path] = $path;
    }
    // End addAttachment


    /**
     * Sets the subject
     *
     * @param text $textContent
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
    // End setSubject


    /**
     * Sends the mail
     *
     * @param  -
     * @return -
     */
    public function send($mailTo, $fullName = null)
    {
        $Mailer = new PHPMailer(true);
        
        if (is_array($mailTo))
        {
            foreach ($mailTo as $email)
                $Mailer->addAddress($email);            
        }
        else        
            $Mailer->addAddress($mailTo, $fullName);

        if ($this->subject)
            $Mailer->Subject = $this->subject;
        else
            throw new Exception("Mail send error: `Subject is empty'");

        $Mailer->From = $this->splitAddress($this->Config->mailFrom, 0);
        $Mailer->FromName = $this->splitAddress($this->Config->mailFrom, 1);
        $Mailer->Sender = $this->Config->mailReturnPath;

        if(isset($this->Config->encoding))
            $Mailer->Encoding = $this->Config->encoding;

        if(isset($this->Config->charset))
            $Mailer->CharSet = $this->Config->charset;

        if($this->htmlContent && $this->textContent)
        {
            $Mailer->MsgHTML($this->htmlContent);
            $Mailer->AltBody = $this->textContent;
        }
        elseif($this->htmlContent && !$this->textContent)
        {
            $Mailer->MsgHTML($this->htmlContent);

            if($altTxt = $this->htmlToText($this->htmlContent))
                $Mailer->AltBody = $altTxt;
        }
        elseif(!$this->htmlContent && $this->textContent)
        {
            $Mailer->isHTML(false);
            $Mailer->Body = $this->textContent;
        }
        else
            throw new Exception("Mail send error: `Mail is empty'");

        if (count($this->attachments))
        {
            foreach ($this->attachments as $path => $foo)
            {
                if (!File::factory($path)->exists())
                    continue;

                $Mailer->AddAttachment($path, '', 'base64', MimeType::getByFilepath($path));
            }
        }        
        
        if(isset($this->Config->wordWrap))
            $Mailer->WordWrap = $this->Config->wordWrap;

        $Mailer->IsSMTP();
        $Mailer->Host = $this->Config->mxServer;

        if(isset($this->Config->port))
            $Mailer->Port = $this->Config->port;

        if(isset($this->Config->helo))
            $Mailer->Helo = $this->Config->helo;

        if(isset($this->Config->auth) &&
            isset($this->Config->user) &&
            isset($this->Config->pass))
        {
            $Mailer->SMTPAuth = (bool)$this->Config->auth;
            $Mailer->Username = $this->Config->user;
            $Mailer->Password = $this->Config->pass;
        }

        if(!$Mailer->Send())
            throw new Exception('Mail send error: `' . (string)$Mailer->ErrorInfo . "'");
    }
    // End sendMail


    /**
     * Splits the address into its two address parts. If no second argument is specified
     * an array with both parts will be returned. If the second argument is set, a value of 0
     * returns the email, a value of 1 returns the alias part
     *
     * @param  -
     * @return mixed
     */
    private function splitAddress($email, $index = null)
    {
        $parts = array();

        if(preg_match('/(\"?([^"]+?)\"?\s*?)?\<(.+?)\>/ui', $email, $matches))
        {
            $parts[0] = trim($matches[3]);
            $parts[1] = trim($matches[2]);
        }
        else
            $parts[0] = trim($email);

        if(is_null($index))
            return $parts;

        return isset($parts[intval($index) % 2])? $parts[intval($index) % 2] : null;
    }
    // End splitAddress


    /**
     * Converts html to text via html2text command
     *
     * @param  string $htmlText
     * @return string
     */
    protected function htmlToText($htmlText)
    {
        if(!isset($this->Config->html2textCmd) ||
           !$html2textCmd = $this->Config->html2textCmd)
            return false;

        if(!$tempFilename = tempnam(sys_get_temp_dir(), 'elcaSendMail'))
            return false;

        $htmlText = $this->fixHtml2Text($htmlText);
        $fh = fopen($tempFilename, 'w');
        fputs($fh, $htmlText);
        fclose($fh);

        $cmd = $html2textCmd .' -nobs -width 250 '. $tempFilename;

        $text = array();
        exec($cmd, $text);
        unlink($tempFilename);

        return join("\r\n", $text);
    }
    // End htmlToText


    /**
     * Fixes some strange behaviours of html2text:
     *
     * <a> save href from links
     *
     * @param  string $htmlText
     * @return string
     */
    protected function fixHtml2Text($htmlText)
    {
        $patterns[]     = '/<a.*?href=\"(.+?)\".*?>(.+?)<\/a>/usi';
        $replacements[] = '$1';

        return preg_replace($patterns, $replacements, $htmlText);
    }
    // End fixHtml2Text

}
// End Mailer
