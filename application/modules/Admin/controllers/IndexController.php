<?php
namespace Admin;

use Currency\Helper\Format;
use Paymentgateway\Order;
use Wallet\TransactionManager;

class IndexController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function accessible($module)
	{
		return $this->isModuleAccessible($module);
	}
	
	public function indexAction() {
            
		if(!$this->accessible('Seller') && !$this->accessible('Store') && !$this->accessible('Poweruser') && !$this->accessible('Vip'))
			$this->redirect($this->url([ 'module' => 'user' ], 'admin_module'));

		$totalIncomeChart = $this->formatChartData($this->getTotalIncomeForCurrentYearByMonth());
		$sellerChart = $this->accessible('Seller') ? $this->formatChartData($this->getSellerSubscriptionIncomeForCurrentYear()) : NULL;
		$powerChart = $this->accessible('Poweruser') ? $this->formatChartData($this->getPowerUserSubscriptionIncomeForCurrentYear()) : NULL;
		$vipChart = $this->accessible('Vip') ? $this->formatChartData($this->getVipPinIncomeForCurrentYearByMonth()) : NULL;
		$depositChart = $this->accessible('Wallet') ? $this->formatChartData($this->getTransactionChartDataByYearByMonth(TransactionManager::Deposit)) : NULL;
		$withdrawChart = $this->accessible('Wallet') ? $this->formatChartData($this->getTransactionChartDataByYearByMonth(TransactionManager::Withdraw)) : NULL;

		$totalIncome = $this->getTotalIncome();
		$totalSellerSubscriptionIncome = $this->accessible('Seller') ? $this->getTotalSellerSubscriptionIncome() :NULL;
		$walletDeposits = $this->accessible('Wallet') ? TransactionManager::amounts(TransactionManager::Deposit) / 100 :NULL;
		$walletDepositsCount = $this->accessible('Wallet') ? TransactionManager::countTransactions(TransactionManager::Deposit) :NULL;
		$walletTransaction = $this->accessible('Wallet') ? TransactionManager::amounts(TransactionManager::Withdraw) / 100  :NULL;
		$walletTransactionCount = $this->accessible('Wallet') ? TransactionManager::countTransactions(TransactionManager::Withdraw) :NULL;
		$ordersTotalAmount = $this->getTotalPurchaseAmount();
		$avgSellerPackageCommission = $this->accessible('Seller') ? \Seller\Helper\Subscription::getAvgPackageCommission() : NULL;
		$feeIncome = $this->getFeeIncome();
		$ordersTotalCount = Order::getCompletedPurchasesCount();
		$completedPurchasesWithFeeCount = Order::getCompletedPurchasesWithFeeCount();
		$powerSubscriptionTotalIncome = $this->accessible('Poweruser') ? $this->getPowerSubscriptionTotalIncome() : NULL;
		$pinSubscriptionTotalIncome = $this->accessible('Vip') ? $this->getPinSubscriptionTotalIncome() : NULL;
		$sellerSubscriptionsCount = $this->accessible('Seller') ? \Seller\Helper\Subscription::getSellerCount() : NULL;
		$vipPinCount = $this->accessible('Vip') ? \Vip\Payment\Subscription::getVipPinCount() : NULL;
		$powerUserCount = $this->accessible('Poweruser') ? \Poweruser\Payment\Subscription::getPowerUserCount() : NULL;
		
		if(trim($this->getRequest()->getUri(),'/') != 'admin')
			$this->forward('error404', [], 'error', 'admin');

		$this->render('index',
			[
			'totalIncome' => $totalIncome,
			'walletDeposits' => $walletDeposits,
			'walletDepositsCount' => $walletDepositsCount,
			'walletTransaction' => $walletTransaction,
			'walletTransactionCount' => $walletTransactionCount,
			'withdrawChart' => $withdrawChart,
			'depositChart' => $depositChart,
			'totalIncomeChart' => $totalIncomeChart,
			'sellerChart' => $sellerChart,
			'powerChart' => $powerChart,
			'vipChart' => $vipChart,
			'ordersTotalAmount' => $ordersTotalAmount,
			'feeIncome' => $feeIncome,
			'avgSellerPackageCommission' => $avgSellerPackageCommission,
			'completedPurchasesWithFeeCount' => $completedPurchasesWithFeeCount,
			'ordersTotalCount' => $ordersTotalCount,
		 	'totalSellerSubscriptionIncome' => $totalSellerSubscriptionIncome,
		 	'sellerSubscriptionsCount' => $sellerSubscriptionsCount,
		 	'powerSubscriptionTotalIncome' => $powerSubscriptionTotalIncome,
		 	'pinSubscriptionTotalIncome' => $pinSubscriptionTotalIncome,
		 	'powerUserCount' => $powerUserCount,
		 	'vipPinCount' => $vipPinCount,
			]
		);
	}

	private function formatChartData($data) {
		$string = '';
		for($i = 1; $i < 13; $i++) {
			if(!isset($data[$i])) {
				$string .= '0, ';
			} else {
				$string .= $data[$i] . ', ';
			}
		}
		return $string;
	}

	private function getFeeIncome(){
		$sum = 0;
		$totalFeeIncome = Order::getTotalFeeIncome();
		foreach($totalFeeIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $sum;
	}

	private function getTotalPurchaseAmount() {
		$totalSubscriptionIncome = Order::getOrdersAmount();
		$sum = 0;

		foreach($totalSubscriptionIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}

		return $sum;
	}

	private function getTotalIncome() {
		$totalSubscriptionIncome = Order::getTotalSubscriptionIncome();
		$sum = 0;
		foreach($totalSubscriptionIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}
		$totalFeeIncome = Order::getTotalFeeIncome();
		foreach($totalFeeIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $sum;
	}

	private function getTotalIncomeForCurrentYearByMonth() {
		$monthAmounts = [];
		$fees = Order::getLatestYearFeeIncomeByMonth();
		foreach($fees as $k => $fee) {
			$monthAmounts[$fee['month_amount']] = isset($monthAmounts[$fee['month_amount']]) ? $monthAmounts[$fee['month_amount']] : 0;
			$monthAmounts[$fee['month_amount']] += Format::convert($fee['amount'], $fee['currency'], \Base\Config::get ( 'config_currency' ));
		}

		$subscriptions = Order::getLatestYearSubscriptionIncomeByMonth();
		foreach($subscriptions as $k => $subscription) {
			$monthAmounts[$subscription['month_amount']] = isset($monthAmounts[$subscription['month_amount']]) ? $monthAmounts[$subscription['month_amount']] : 0;
			$monthAmounts[$subscription['month_amount']] += Format::convert($subscription['amount'], $subscription['currency'], \Base\Config::get ( 'config_currency' ));
		}

		return $monthAmounts;
	}

	private function getSellerSubscriptionIncomeForCurrentYear() {
		$monthAmounts = [];
		$subscriptions = \Seller\Payment\Subscription::getSellerSubscriptionIncomeByMonthForLastYear();
		foreach($subscriptions as $k => $subscription) {
			$monthAmounts[$subscription['month_amount']] = isset($monthAmounts[$subscription['month_amount']]) ? $monthAmounts[$subscription['month_amount']] : 0;
			$monthAmounts[$subscription['month_amount']] += Format::convert($subscription['amount'], $subscription['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $monthAmounts;
	}

	private function getPowerUserSubscriptionIncomeForCurrentYear() {
		$monthAmounts = [];
		$subscriptions = $this->accessible('Poweruser') ? \Poweruser\Payment\Subscription::getPowerSubscriptionTotalIncomeForCurrentYearByMonth() : NULL;

		if(!$subscriptions)
			return [];

		foreach($subscriptions as $k => $subscription) {
			$monthAmounts[$subscription['month_amount']] = isset($monthAmounts[$subscription['month_amount']]) ? $monthAmounts[$subscription['month_amount']] : 0;
			$monthAmounts[$subscription['month_amount']] += Format::convert($subscription['amount'], $subscription['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $monthAmounts;
	}

	private function getVipPinIncomeForCurrentYearByMonth() {
		$monthAmounts = [];
		$subscriptions = $this->accessible('Vip') ? \Vip\Payment\Subscription::getPinSubscriptionTotalIncomeForCurrentYearByMonth() : NULL;

		if(!$subscriptions)
			return [];

		foreach($subscriptions as $k => $subscription) {
			$monthAmounts[$subscription['month_amount']] = isset($monthAmounts[$subscription['month_amount']]) ? $monthAmounts[$subscription['month_amount']] : 0;
			$monthAmounts[$subscription['month_amount']] += Format::convert($subscription['amount'], $subscription['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $monthAmounts;
	}

	private function getTransactionChartDataByYearByMonth($type) {
		$monthAmounts = [];
		$transactions = TransactionManager::getTransactionsForCurrentYearByMonth($type);
		if(!$transactions)
			return [];
		
		foreach($transactions as $k => $transaction) {
			$monthAmounts[$transaction['month_amount']] = isset($monthAmounts[$transaction['month_amount']]) ? $monthAmounts[$transaction['month_amount']] : 0;
			$monthAmounts[$transaction['month_amount']] += $transaction['amount'] / 100;
		}
		return $monthAmounts;
	}

	private function getPowerSubscriptionTotalIncome() {
		$powerSubscriptionTotalIncome = $this->accessible('Poweruser') ? \Poweruser\Payment\Subscription::getPowerSubscriptionTotalIncome() : NULL;
		$sum = 0;

		if(!$powerSubscriptionTotalIncome)
			return 0;

		foreach($powerSubscriptionTotalIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $sum;
	}

	private function getPinSubscriptionTotalIncome() {
		$pinSubscriptionTotalIncome = $this->accessible('Vip') ? \Vip\Payment\Subscription::getPinSubscriptionTotalIncome() : NULL;
		$sum = 0;

		if(!$pinSubscriptionTotalIncome)
			return 0;

		foreach($pinSubscriptionTotalIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $sum;
	}

	private function getTotalSellerSubscriptionIncome() {
		$totalSellerSubscriptionIncome = $this->accessible('Seller') ? \Seller\Payment\Subscription::getSellerSubscriptionIncome() : NULL;
		$sum = 0;

		if(!$totalSellerSubscriptionIncome)
			return 0;

		foreach($totalSellerSubscriptionIncome as $k => $currency) {
			$sum += Format::convert($currency['amount'], $currency['currency'], \Base\Config::get ( 'config_currency' ));
		}
		return $sum;
	}
	
}