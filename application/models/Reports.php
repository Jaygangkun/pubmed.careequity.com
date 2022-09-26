<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Reports extends CI_Model
{
	public function add($data)
	{
		$query = "INSERT INTO reports(`title`, `conditions`, `study`, `country`, `terms`, `created_at`, `status`, `user_id`) VALUES('" . $data['title'] . "', '" . $data['conditions'] . "', '" . $data['study'] . "', '" . $data['country'] . "', '" . $data['terms'] . "', NOW(), 'no', " . $data['user_id'] . ")";
		$this->db->query($query);

		return $this->db->insert_id();
	}

	public function load($user_id)
	{
		//$query = "SELECT * FROM reports WHERE user_id='" . $user_id . "' ORDER BY title ASC";
		$query = "SELECT * FROM reports ORDER BY title ASC";
		$query_result = $this->db->query($query)->result_array();

		return $query_result;
	}

	public function getByID($id)
	{
		$query = "SELECT * FROM reports WHERE id='" . $id . "'";
		$query_result = $this->db->query($query)->result_array();

		return $query_result;
	}

	public function update($data)
	{

		$query = "UPDATE reports SET title='" . $data['title'] . "', conditions='" . $data['conditions'] . "', study='" . $data['study'] . "', country='" . $data['country'] . "', terms='" . $data['terms'] . "' WHERE id='" . $data['id'] . "'";

		return $this->db->query($query);
	}

	public function deleteByID($id)
	{
		$query = "DELETE FROM reports WHERE id='" . $id . "'";
		return $this->db->query($query);
	}

	public function duplicateByID($id)
	{

		$query_get = "SELECT * FROM reports WHERE id='" . $id . "'";
		$results_get = $this->db->query($query_get)->result_array();

		if (count($results_get) > 0) {
			$report = $results_get[0];

			// clone
			$query = "INSERT INTO reports(`title`, `conditions`, `study`, `country`, `terms`, `created_at`, `user_id`) VALUES('" . $report['title'] . "', '" . $report['conditions'] . "', '" . $report['study'] . "', '" . $report['country'] . "', '" . $report['terms'] . "', NOW(), '" . $report['user_id'] . "')";
			$this->db->query($query);

			return $this->db->insert_id();
		}

		return null;
	}

	public function updateField($data)
	{
		$update_set = '';
		if (isset($data['reporting'])) {
			if ($update_set == '') {
				$update_set = "reporting='" . $data['reporting'] . "'";
			}
		}

		if (isset($data['status'])) {
			if ($update_set == '') {
				$update_set = "status='" . $data['status'] . "'";
			} else {
				$update_set .= ", status='" . $data['status'] . "'";
			}
		}





		$query = "UPDATE reports SET " . $update_set . " WHERE id='" . $data['id'] . "'";

		return $this->db->query($query);
	}

	public function updateWeek($data)
	{
		$update_set = '';
		if (isset($data['week_list'])) {
			if ($update_set == '') {
				$update_set = "week_list='" . $data['week_list'] . "'";
			}
		}

		if (isset($data['week_reports'])) {
			if ($update_set == '') {
				$update_set = "week_reports='" . $data['week_reports'] . "'";
			} else {
				$update_set .= ", week_reports='" . $data['week_reports'] . "'";
			}
		}





		$query = "UPDATE reports SET " . $update_set . " WHERE id='" . $data['id'] . "'";

		return $this->db->query($query);
	}


	public function updateOnReporters($data){

		$update_set = "on_reporters='".$data['on_reporters']."'";
		$query = "UPDATE reports SET ".$update_set." WHERE id='".$data['id']."'";		

		return $this->db->query($query);
	}



	public function search($keyword, $sort, $user_id)
	{

		$query = '';

		if ($sort == 'az') {
			$orderby = 'ORDER BY title ASC';
		} else if ($sort == 'newold') {
			$orderby = 'ORDER BY status ASC';
		} else if ($sort == 'oldnew') {
			$orderby = 'ORDER BY status DESC';
		}

		if ($keyword == '') {
			$query = "SELECT * FROM reports WHERE user_id='" . $user_id . "' " . $orderby;
		} else {
			$query = "SELECT * FROM reports WHERE (title LIKE '%" . $keyword . "%' OR conditions LIKE '%" . $keyword . "%' OR study LIKE '%" . $keyword . "%' OR country LIKE '%" . $keyword . "%' OR terms LIKE '%" . $keyword . "%') AND user_id='" . $user_id . "' " . $orderby;
		}

		$query_result = $this->db->query($query)->result_array();
		return $query_result;
	}

	public function allActiveReports()
	{
		$query = "SELECT * FROM reports WHERE reporting='1' AND user_id IS NOT NULL AND user_id <> ''";
		$query_result = $this->db->query($query)->result_array();
		return $query_result;
	}


	public function allSavedReports($report_id)
	{
		$query = "SELECT * FROM report_recorder WHERE report_id='" . $report_id . "' GROUP BY guid";
		$query_result = $this->db->query($query)->result_array();
		return $query_result;
	}




	public function updateGuids($report_id, $data)
	{
		$query = "UPDATE reports SET pubDate='" . $data['pubDate'] . "', guids='" . $data['guids'] . "' WHERE id='" . $report_id . "'";
		return $this->db->query($query);
	}

	public function updateOnlyGuids($report_id, $data)
	{
		$query = "UPDATE reports SET  updated_at='" . $data['updated_at'] . "', guids='" . $data['guids'] . "' WHERE id='" . $report_id . "'";
		return $this->db->query($query);
	}


	public function recordReports($report_id, $data)
	{



		foreach ($data['clinics'] as $clinic) {


			//$mysqli = new mysqli("localhost", "root", "", "clinical_rss");
			$mysqli = new mysqli("localhost", "evan8ce_root", "8^BhZyBC5l&B", "evan8ce_pubmed");

			$report_title = $mysqli->real_escape_string($data['title']);
			$title = $mysqli->real_escape_string($clinic['title']);
			$link = $mysqli->real_escape_string($clinic['link']);
			$description = $mysqli->real_escape_string($clinic['description']);
			$creator = $mysqli->real_escape_string($clinic['creator']);
			$identifier = $mysqli->real_escape_string($clinic['identifier']);



			$query = "INSERT INTO report_recorder(`report_id`, `report_title`, `title`, `link`, `description`, `guid`, `pubdate`, `author`, `identifier`, `created_at`) VALUES('" . $report_id . "', '" . $report_title . "', '" . $title . "', '" . $link . "', '" . $description . "', '" . $clinic['guid'] . "', '" . $clinic['pubdate'] . "', '" . $creator . "', '" . $identifier . "', NOW())";
			$this->db->query($query);

			//return $this->db->insert_id();

		}


		return null;
	}


	public function deleteReports($report_id)
	{
		$query = "DELETE FROM report_recorder WHERE report_id='" . $report_id . "' AND created_at < NOW() - interval 2 DAY";
		return $this->db->query($query);
	}
}
