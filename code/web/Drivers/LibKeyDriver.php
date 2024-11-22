<?php

class LibKeyDriver {

	public function getLibKeyLink($doi) {
		require_once ROOT_DIR . '/sys/LibKey/LibKeySetting.php';
		$activeLibrary = Library::getActiveLibrary();
		$settings = new LibKeySetting();
		$settings->whereAdd("id=$activeLibrary->libKeySettingId");
		if ($settings->find(true)) {
			$settings->fetch();
		}
		$curlWrapper = new CurlWrapper;
		$response = $curlWrapper->curlGetPage("https://public-api.thirdiron.com/public/v1/libraries/" . $settings->libraryId  . "/articles/doi/" . $doi . "?access_token=" . $settings->apiKey);
		if (empty($response)) {
			return null;
		}
		return json_decode($response, true)["data"]["bestIntegratorLink"]["bestLink"];
	}
}