<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/vendor/autoload.php';

require_once APPPATH . "libraries/PHPMailer/Exception.php";
require_once APPPATH . "libraries/PHPMailer/PHPMailer.php";
require_once APPPATH . "libraries/PHPMailer/SMTP.php";

use Dompdf\Dompdf;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CronJobController extends CI_Controller
{

	public function __construct()
	{

		parent::__construct();

		$this->load->model("Reports");
		$this->load->model("Users");

		$this->load->library('mailer');
	}

	public function test()
	{
		writeLog('Test Log');

		$this->mailer->sendTestMail();
		die();
		$mail = new PHPMailer();

		$mail->IsSMTP();
		$mail->Host = 'mail.pubmed.careequity.com';
		$mail->Port = 465;
		$mail->SMTPAuth = true;
		$mail->Username = 'pubmed@pubmed.careequity.com';
		$mail->Password = 'M?r;=[_Wq631';
		$mail->SMTPSecure = 'ssl';
		$mail->SMTPDebug  = 1;
		$mail->SMTPAuth   = TRUE;

		$mail->From = 'pubmed@pubmed.careequity.com';
		$mail->FromName = 'Clinic';

		$mail->Subject = "Message from contact form";
		$mail->Body    = "This is test email";
		$mail->AddAddress('kingdeveloper@yahoo.com');

		$mail->addAttachment('searchresults/Test Attachment.pdf');

		if (!$mail->Send()) {
			echo $mail->ErrorInfo;
		}

		echo "success";
	}



	public function record()
	{
		set_time_limit(0);

		writeLog('Record Start >>>>>>>>>>');



		// get active reports
		$reports = $this->Reports->allActiveReports();

		foreach ($reports as $report) {






			$delete_recordreports = $this->Reports->deleteReports($report['id']);

			//original search text
			if ($report['terms'] != "") {
				if ($report['study'] == "All Fields") {
					$origin_search_str = "(" . $report['conditions'] . ")" . " " . $report['country'] . " (" . $report['terms'] . ")";
				} else {
					$origin_search_str = "(" . $report['conditions'] . "[" . $report['study'] . "])" . " " . $report['country'] . " (" . $report['terms'] . ")";
				}
			} else {
				if ($report['study'] == "All Fields") {
					$origin_search_str = $report['conditions'];
				} else {
					$origin_search_str = "(" . $report['conditions'] . "[" . $report['study'] . "])";
				}
			}


			$encode_search_str = urlencode($origin_search_str);

			//echo $encode_search_str;

			//return $encode_search_str;

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://pubmed.ncbi.nlm.nih.gov/?term=' . $encode_search_str,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Cookie: pm-csrf=JC0Dxz9RiYe8HS2ABzKGJmaH4c0PCu1qrCdKIhCYOmICpcLyQcpjfqt9Yju2XR5j; ncbi_sid=0B4081E50BEBF073_6576SID; pm-sid=2BUVbBZqGAn5YC8AlF89AA:99ebd61350f6e9c860fadd860b152d9f; pm-adjnav-sid=LBoYi1uNq4WefkTMc2C7sg:99ebd61350f6e9c860fadd860b152d9f; pm-sessionid=fqnxq8494p4uvlsqzjpfxska6npsf01g'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);

			$cut_str = '<h1 class="usa-sr-only">Search Page</h1>';
			$s_list = explode($cut_str, $response);

			$cut_str1 = '<form action="/" method="get" autocomplete="off"';
			$rea_val = explode($cut_str1, $s_list[1]);



			$cut_str2 = '<input type="hidden" name="csrfmiddlewaretoken" value="';
			$rea_val2 = explode($cut_str2, $rea_val[0]);

			$csr_key = substr($rea_val2[1], 0, -3);


			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://pubmed.ncbi.nlm.nih.gov/create-rss-feed-url/',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => array('name' => '' . $origin_search_str . '', 'term' => '' . $origin_search_str . '', 'limit' => '100', 'csrfmiddlewaretoken' => '' . $csr_key . ''),
				CURLOPT_HTTPHEADER => array(
					'referer: https://pubmed.ncbi.nlm.nih.gov/?term=' . $encode_search_str . '',
					'Cookie: pm-csrf=JC0Dxz9RiYe8HS2ABzKGJmaH4c0PCu1qrCdKIhCYOmICpcLyQcpjfqt9Yju2XR5j; ncbi_sid=0B4081E50BEBF073_6576SID; pm-sid=2BUVbBZqGAn5YC8AlF89AA:99ebd61350f6e9c860fadd860b152d9f; pm-adjnav-sid=LBoYi1uNq4WefkTMc2C7sg:99ebd61350f6e9c860fadd860b152d9f; pm-sessionid=fqnxq8494p4uvlsqzjpfxska6npsf01g'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);

			$cut_str3 = '{"rss_feed_url": "';
			$s_list1 = explode($cut_str3, $response);

			$cut_str4 = '"';
			$rea_val3 = explode($cut_str4, $s_list1[1]);





			$rss_url = $rea_val3[0];

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => '' . $rss_url . '',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Cookie: pm-csrf=JC0Dxz9RiYe8HS2ABzKGJmaH4c0PCu1qrCdKIhCYOmICpcLyQcpjfqt9Yju2XR5j; pm-sid=2BUVbBZqGAn5YC8AlF89AA:99ebd61350f6e9c860fadd860b152d9f; pm-adjnav-sid=LBoYi1uNq4WefkTMc2C7sg:99ebd61350f6e9c860fadd860b152d9f; ncbi_sid=0B4081E50BEBF073_6576SID'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			//echo $response;

			$xml = new SimpleXMLElement($response);
			//echo $xml;

			$data = array(
				'clinics' => array(),
				'title' => $xml->channel->title
			);

			$guids = array();

			$terms = $report['terms'];
			$study = $report['study'];
			$conditions = $report['conditions'];
			$country = $report['country'];
			$old_guids = $report['guids'];
			$new_updated_at =  $report['updated_at'];

			$guids =  array();

			$changed = false;

			$db_guids = array();

			$total_day = 0;

			if ($report['guids'] != "") {
				$db_guids = json_decode($report['guids'], true);
			}


			$changed = false;

			//$xml = new SimpleXmlElement("doc1.xml",null,true);
			foreach ($xml->channel->item as $item) {


				$namespaces = $item->getNameSpaces(true);
				$cds = $item->children($namespaces['dc']);

				$details['creator'] = "";
				$details['identifier'] = "";

				foreach ($cds as $name => $c) {
					//echo $name, '=', $c, "\n";

					if ($name == "creator") {

						if ($details['creator'] == "") {
							$details['creator'] .= $c;
						} else {
							$details['creator'] .= "<br>" . $c;
						}
					}

					if ($name == "identifier") {

						if ($details['identifier'] == "") {
							$details['identifier'] .= $c;
						} else {
							$details['identifier'] .= "<br>" . $c;
						}
					}
				}

				$details['link'] = $item->link;
				$details['title'] = $item->title;
				$details['description'] = $item->description;
				$details['guid'] = $item->guid;
				$details['pubdate'] = $item->pubDate;


				$data['clinics'][] = $details;
			}





			$record_reports = $this->Reports->recordReports($report['id'], $data);
		}


		//echo "Send Successfully!";
	}

	public function run()
	{
		set_time_limit(0);
		writeLog('Cron Job Start >>>>>>>>>>');

		// get active reports
		$reports = $this->Reports->allActiveReports();

		foreach ($reports as $report) {


			//writeLog('---Start the report:' . $report['id']);

			//original search text
			if ($report['terms'] != "") {
				if ($report['study'] == "All Fields") {
					$origin_search_str = "(" . $report['conditions'] . ")" . " " . $report['country'] . " (" . $report['terms'] . ")";
				} else {
					$origin_search_str = "(" . $report['conditions'] . "[" . $report['study'] . "])" . " " . $report['country'] . " (" . $report['terms'] . ")";
				}
			} else {
				if ($report['study'] == "All Fields") {
					$origin_search_str = $report['conditions'];
				} else {
					$origin_search_str = "(" . $report['conditions'] . "[" . $report['study'] . "])";
				}
			}


			$encode_search_str = urlencode($origin_search_str);

			//echo $encode_search_str;

			//return $encode_search_str;


			//Orignial

			/*
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://pubmed.ncbi.nlm.nih.gov/?term=' . $encode_search_str,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Cookie: pm-csrf=JC0Dxz9RiYe8HS2ABzKGJmaH4c0PCu1qrCdKIhCYOmICpcLyQcpjfqt9Yju2XR5j; ncbi_sid=0B4081E50BEBF073_6576SID; pm-sid=2BUVbBZqGAn5YC8AlF89AA:99ebd61350f6e9c860fadd860b152d9f; pm-adjnav-sid=LBoYi1uNq4WefkTMc2C7sg:99ebd61350f6e9c860fadd860b152d9f; pm-sessionid=fqnxq8494p4uvlsqzjpfxska6npsf01g'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);

			$cut_str = '<h1 class="usa-sr-only">Search Page</h1>';
			$s_list = explode($cut_str, $response);

			$cut_str1 = '<form action="/" method="get" autocomplete="off"';
			$rea_val = explode($cut_str1, $s_list[1]);



			$cut_str2 = '<input type="hidden" name="csrfmiddlewaretoken" value="';
			$rea_val2 = explode($cut_str2, $rea_val[0]);

			$csr_key = substr($rea_val2[1], 0, -3);


			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://pubmed.ncbi.nlm.nih.gov/create-rss-feed-url/',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => array('name' => '' . $origin_search_str . '', 'term' => '' . $origin_search_str . '', 'limit' => '100', 'csrfmiddlewaretoken' => '' . $csr_key . ''),
				CURLOPT_HTTPHEADER => array(
					'referer: https://pubmed.ncbi.nlm.nih.gov/?term=' . $encode_search_str . '',
					'Cookie: pm-csrf=JC0Dxz9RiYe8HS2ABzKGJmaH4c0PCu1qrCdKIhCYOmICpcLyQcpjfqt9Yju2XR5j; ncbi_sid=0B4081E50BEBF073_6576SID; pm-sid=2BUVbBZqGAn5YC8AlF89AA:99ebd61350f6e9c860fadd860b152d9f; pm-adjnav-sid=LBoYi1uNq4WefkTMc2C7sg:99ebd61350f6e9c860fadd860b152d9f; pm-sessionid=fqnxq8494p4uvlsqzjpfxska6npsf01g'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);

			$cut_str3 = '{"rss_feed_url": "';
			$s_list1 = explode($cut_str3, $response);

			$cut_str4 = '"';
			$rea_val3 = explode($cut_str4, $s_list1[1]);





			$rss_url = $rea_val3[0];

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => '' . $rss_url . '',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Cookie: pm-csrf=JC0Dxz9RiYe8HS2ABzKGJmaH4c0PCu1qrCdKIhCYOmICpcLyQcpjfqt9Yju2XR5j; pm-sid=2BUVbBZqGAn5YC8AlF89AA:99ebd61350f6e9c860fadd860b152d9f; pm-adjnav-sid=LBoYi1uNq4WefkTMc2C7sg:99ebd61350f6e9c860fadd860b152d9f; ncbi_sid=0B4081E50BEBF073_6576SID'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			//echo $response;

			$xml = new SimpleXMLElement($response);
			//echo $xml;

			*/

			//get saved reports



			$data = array(
				'clinics' => array(),
				'title' => ""
			);
			/*
			$data = array(
				'clinics' => array(),
				'title' => $xml->channel->title
			);
			*/
			$guids = array();

			$terms = $report['terms'];
			$study = $report['study'];
			$conditions = $report['conditions'];
			$country = $report['country'];
			$old_guids = $report['guids'];
			$new_updated_at =  $report['updated_at'];

			$guids =  array();

			$changed = false;

			$db_guids = array();

			$total_day = 0;

			if ($report['guids'] != "") {
				$db_guids = json_decode($report['guids'], true);
			}


			$changed = false;

			//$xml = new SimpleXmlElement("doc1.xml",null,true);

			$saved_reports = $this->Reports->allSavedReports($report['id']);




			foreach ($saved_reports as $saved_report) {





				$pos = strpos($report['guids'], $saved_report['guid']);

				//writeLog('old:' . $report['guids']);
				//writeLog('---current:' . $saved_report['guid']);
				//WriteLog('Pos count:' . $pos);

				if ($pos === false) {

					$details['creator'] =  $saved_report['author'];
					$details['identifier'] =  $saved_report['identifier'];

					$details['link'] =  $saved_report['link'];
					$details['title'] =  $saved_report['title'];
					$details['description'] =  $saved_report['description'];
					$details['guid'] =  $saved_report['guid'];
					$details['pubdate'] =  $saved_report['pubdate'];


					$data['clinics'][] = $details;
				}


				$guids[] =  $saved_report['guid'];


				//writeLog('--current saved report:' . $saved_report['guid']);

				$data['title'] =  $saved_report['report_title'];
			}

			/*
			foreach ($xml->channel->item as $item) {



				$pos = strpos($report['guids'], $item->guid->__toString());

				writeLog('old:' . $report['guids']);
				writeLog('---current:' . $item->guid);
				WriteLog('Pos count:' . $pos);

				if ($pos === false) {



					$namespaces = $item->getNameSpaces(true);
					$cds = $item->children($namespaces['dc']);

					$details['creator'] = "";
					$details['identifier'] = "";

					foreach ($cds as $name => $c) {
						//echo $name, '=', $c, "\n";

						if ($name == "creator") {

							if ($details['creator'] == "") {
								$details['creator'] .= $c;
							} else {
								$details['creator'] .= "<br>" . $c;
							}
						}

						if ($name == "identifier") {

							if ($details['identifier'] == "") {
								$details['identifier'] .= $c;
							} else {
								$details['identifier'] .= "<br>" . $c;
							}
						}
					}

					$details['link'] = $item->link;
					$details['title'] = $item->title;
					$details['description'] = $item->description;
					$details['guid'] = $item->guid;
					$details['pubdate'] = $item->pubDate;


					$data['clinics'][] = $details;
				}


				$guids[] = $item->guid->__toString();
			}
			*/


			$found_count = 0;
			foreach ($guids as $guid) {
				if (strpos($report['guids'], $guid) !== false) {
					$found_count++;
				}
			}

			if ($found_count != count($db_guids) || $found_count != count($guids)) {
				$changed = true;
			}


			if ($changed) {
				$dompdf = new Dompdf();

				$clinic_html = $this->load->view('admin/template/clinic-table', $data, TRUE);
				// echo $clinic_html;die();
				$dompdf->loadHtml($clinic_html);

				// (Optional) Setup the paper size and orientation
				$dompdf->setPaper('A3', 'landscape');

				// Render the HTML as PDF
				$dompdf->render();

				// Output the generated PDF to Browser

				// $dompdf->stream("SearchResults.pdf");
				$output = $dompdf->output();
				$filepath = 'searchresults/Search Results_' . $report['id'] . "_" . time() . '.pdf';
				file_put_contents($filepath, $output);


				$on_reporters = $report['on_reporters'];

				if ($on_reporters == null || $on_reporters == '') {
				} else {
					
					$user_ids = explode(",", $on_reporters);

					//foreach loop to display the returned array
					foreach ($user_ids as $i) {
						
					$users = $this->Users->getByID($i);

					if (count($users) > 0) {
						$user = $users[0];

						// sending email
						$mail = new PHPMailer();

						$mail->IsSMTP();
						$mail->Host = 'mail.pubmed.careequity.com';
						$mail->Port = 465;
						$mail->SMTPAuth = true;
						$mail->Username = 'pubmed@pubmed.careequity.com';
						$mail->Password = 'M?r;=[_Wq631';
						$mail->SMTPSecure = 'ssl';
						$mail->SMTPDebug  = 1;
						$mail->SMTPAuth   = TRUE;

						$mail->From = 'careequitypubmedtool@pubmed.careequity.com';
						$mail->FromName = 'Care Equity Pubmed tool';

						$mail->Subject = "Message from Care Equity Pubmed tool";
						//$mail->Body    = $xml->channel->title;
						$mail->Body = $data['title'];

						$mail->AddAddress($user['email']);

						$mail->addAttachment($filepath);

						if (!$mail->Send()) {
							writeLog($mail->ErrorInfo);
						}

						//writeLog('---Sent Email to ' . $user['email']);
					}

					$cur_updated_at =  date("Ymd");
					}
				}



				/*
				$reports = $this->Reports->updateOnlyGuids($report['id'], array(
					'updated_at' => $cur_updated_at,
					'guids' => json_encode($guids)
				));
				*/
			} else {
				//writeLog('---No Changes');
			}



			//writeLog('---Complete the report:' . $report['id']);
		}

		//writeLog('Cron Job End <<<<<<<<<<');
		//echo "Send Successfully!";
	}

	public function check()
	{

		set_time_limit(0);

		//writeLog('Cron Job Check Start >>>>>>>>>>');

		// get active reports
		$reports = $this->Reports->allActiveReports();

		foreach ($reports as $report) {
			//writeLog('---Start Check:' . $report['id']);


			$terms = $report['terms'];
			$study = $report['study'];
			$conditions = $report['conditions'];
			$country = $report['country'];
			$old_guids = $report['guids'];
			$new_updated_at =  $report['updated_at'];

			$guids =  array();

			$changed = false;

			$db_guids = array();

			$total_day = 0;

			if ($report['guids'] != "") {
				$db_guids = json_decode($report['guids'], true);
			}


			$found_count = 0;

			//Getting week_list and week_reports
			$current_week_list = $report['week_list'];
			$current_week_reports = $report['week_reports'];

			if ($report['reporting'] == '1') {

				if ($report['updated_at'] == "" || $report['updated_at'] == null) {

					$cur_updated_at =  date("Ymd");

					$guids = getPubmedGuids(array(
						'days' => 7,
						'terms' => $terms,
						'study' => $study,
						'conditions' => $conditions,
						'country' => $country,
						'count' => 10
					));

					// echo $guids;

					$reports = $this->Reports->updateOnlyGuids($report['id'], array(
						'updated_at' => $cur_updated_at,
						'guids' => json_encode($guids)
					));

					$found_count = count($guids);
					$status = 'new';
				} else {

					$new_updated_at =  date("Ymd", strtotime($report['updated_at']));
					$cur_updated_at = date("Ymd");
					$old_month = intval(substr($new_updated_at, 4, 2));
					$old_day = intval(substr($new_updated_at, 6, 2));

					$new_month = intval(substr($cur_updated_at, 4, 2));
					$new_day = intval(substr($cur_updated_at, 6, 2));

					$total_month = 0;


					if ($old_month == 12 && $new_month == 1) {
						$total_month = 1 * 30;
					} else {
						$total_month = ($new_month - $old_month) * 30;
						$total_day = $new_day - $old_day;
					}

					$total_days = $total_month + $total_day;


					/*
					$guids = getPubmedGuids(array(
						'days' => 7,
						'terms' => $terms,
						'study' => $study,
						'conditions' => $conditions,
						'country' => $country,
						'count' => 10
					));
					*/

					$saved_reports = $this->Reports->allSavedReports($report['id']);


					//writeLog('---count saved reports:' . count($saved_reports));
					foreach ($saved_reports as $saved_report) {
						if (strpos($report['guids'], $saved_report['guid']) === false) {
							$found_count++;
						}

						//writeLog('---saved reports guid:' .$saved_report['guid'] );
						$guids[] =  $saved_report['guid'];
					}

					/*
					foreach ($guids as $guid) {
						if (strpos($report['guids'], $guid) === false) {
							$found_count++;
						}
					}
					*/

					if ($found_count != count($db_guids) || $found_count != count($guids)) {
						$changed = true;
					}


					if ($changed) {

						//writeLog('---changed :' .json_encode($guids) );
						//writeLog('---found_count :' .$found_count );
						$status = 'new';
						$reports = $this->Reports->updateOnlyGuids($report['id'], array(
							'updated_at' => $cur_updated_at,
							'guids' => json_encode($guids)
						));
					} else {
						if ($total_days > 7 && $total_days <= 30) {
							$status = 'recent';
						} else if ($total_days > 30) {
							$status = 'old';
						} else if ($total_days <= 7) {
							$status = 'new';
						}
					}
				}




				if ($current_week_list == "" && $current_week_reports == "") {
					$week_update = $this->Reports->updateWeek(array(
						'id' => $report['id'],
						'week_list' => '1',
						'week_reports' => $found_count
					));
				} else {

					$org_list = explode(",", $current_week_list);




					$new_week_list = "";
					$new_week_reports = "";

					$last_week = end($org_list) + 1;
					$new_week_list = $current_week_list . "," . $last_week;

					$new_week_reports = $current_week_reports . "," . $found_count;



					$week_update = $this->Reports->updateWeek(array(
						'id' => $report['id'],
						'week_list' => $new_week_list,
						'week_reports' => $new_week_reports
					));
				}
			} else {
				$status = 'no';
				$cur_updated_at = $report['updated_at'];


				$week_update = $this->Reports->updateWeek(array(
					'id' => $report['id'],
					'week_list' => '',
					'week_reports' => ''
				));
			}

			/*
			$report_update = $this->Reports->updateField(array(
                'id' => $report['id'],
                'status' => $status
            ));

			if($report_update){
				writeLog('---Update Status:'.$status);	
			}*/




			$report_update = $this->Reports->updateField(array(
				'id' => $report['id'],
				'reporting' => $report['reporting'],
				'status' => $status
			));


			if ($report_update) {
				writeLog('---Update Status:' . $status);
			}
			//writeLog('---Complete Check:' . $report['id']);
		}

		writeLog('Cron Job Check End <<<<<<<<<<');
		//echo " Check Successfully!";
	}
}
