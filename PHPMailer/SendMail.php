<?php
	class SendMail{
		private $config=array(
			'smtp_host'				=> 'smtp.163.com', 
			'smtp_port'				=> '25', 
			'from_email'			=> 'liubiao0913@163.com', 
			'from_name'				=> 'AA', 
			'smtp_user'				=> 'liubiao0913@163.com', 
			'smtp_pass'				=> 'XXX', 
			'reply_email'			=> '',
			'reply_name'			=> '',
		);

		public function __construct($config=''){
			if(is_array($config)){
				$this->config =$config;
			}
		}


		/**
		  +----------------------------------------------------------
		 * 功能：系统邮件发送函数
		  +----------------------------------------------------------
		 * @param string $to    接收邮件者邮箱
		 * @param string $name  接收邮件者名称
		 * @param string $subject 邮件主题
		 * @param string $body    邮件内容
		 * @param string $attachment 附件列表
		  +----------------------------------------------------------
		 * @return boolean
		  +----------------------------------------------------------
		 */
		function send_mail($to, $name, $subject = '', $body = '', $attachment = null) {				
			include 'phpmailer.class.php';						//从PHPMailer目录导class.phpmailer.php类文件
			$mail = new \PHPMailer();							//PHPMailer对象
			$mail->CharSet = 'UTF-8';							//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
			$mail->IsSMTP();									// 设定使用SMTP服务
			//$mail->IsHTML(true);
			$mail->SMTPDebug = 0;								// 关闭SMTP调试功能 1 = errors and messages2 = messages only
			$mail->SMTPAuth = true;								// 启用 SMTP 验证功能
			if ($this->config['smtp_port'] == 465){
				$mail->SMTPSecure = 'ssl';						// 使用安全协议
			}	
			$mail->Host = $this->config['smtp_host'];			// SMTP 服务器
			$mail->Port = $this->config['smtp_port'];			// SMTP服务器的端口号
			$mail->Username = $this->config['smtp_user'];		// SMTP服务器用户名
			$mail->Password = $this->config['smtp_pass'];		// SMTP服务器密码
			$mail->SetFrom($this->config['from_email'], $this->config['from_name']);
			$replyEmail = $this->config['reply_email'] ? $this->config['reply_email'] : $this->config['reply_email'];
			$replyName = $this->config['reply_name'] ? $this->config['reply_name'] : $this->config['reply_name'];
			$mail->AddReplyTo($replyEmail, $replyName);
			$mail->Subject = $subject;
			$mail->MsgHTML($body);
			$mail->AddAddress($to, $name);
			if (is_array($attachment)) { // 添加附件
				foreach ($attachment as $file) {
					if (is_array($file)) {
						is_file($file['path']) && $mail->AddAttachment($file['path'], $file['name']);
					} else {
						is_file($file) && $mail->AddAttachment($file);
					}
				}
			} else {
				is_file($attachment) && $mail->AddAttachment($attachment);
			}
			return $mail->Send() ? true : $mail->ErrorInfo;
		}
	}