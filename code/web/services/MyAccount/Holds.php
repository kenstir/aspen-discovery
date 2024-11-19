<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Holds extends MyAccount {
	function launch() : void {
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();

		$tab = $_REQUEST['tab'] ?? 'all';
		$interface->assign('tab', $tab);

		if ($library->showLibraryHoursNoticeOnAccountPages) {
			$libraryHoursMessage = Location::getLibraryHoursMessage($user->homeLocationId);
			$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		}

		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		$interface->assign('profile', $user);
		$this->display('holds.tpl', 'Titles On Hold');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Titles On Hold');
		return $breadcrumbs;
	}
}
