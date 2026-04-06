<?php

namespace MediaWiki\Extension\WRNewArticles;

use ExtensionRegistry;
use MediaWiki\Extension\ArticleContentArea\ArticleContentArea;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Title\TitleFactory;

class SpecialNewArticles extends FormSpecialPage {

	private TitleFactory $titleFactory;

	public function __construct( TitleFactory $titleFactory ) {
		parent::__construct( 'NewArticles' );
		$this->titleFactory = $titleFactory;
	}

	protected function getGroupName() {
		return 'changes';
	}

	public function onSubmit( array $data ) {
		$contentarea = $this->getRequest()->getVal( 'contentarea' );

		$pager = new NewArticlesPager( $this->getContext(), $contentarea, $this->titleFactory );

		$logBody = $pager->getBody();
		if ( $logBody ) {
			$this->getOutput()->addHTML(
				$pager->getNavigationBar() .
				$logBody .
				$pager->getNavigationBar()
			);
		} else {
			$this->getOutput()->addWikiMsg( 'logempty' );
		}
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setMethod( 'get' );
	}

	protected function getFormFields() {
		$fields = [];
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ArticleContentArea' ) ) {
			$fields['contentarea'] = [
				'type'          => 'select',
				'name'          => 'contentarea',
				'label-message' => 'newarticles-filter-contentarea',
				'options'       => self::getContentAreaOptions(),
			];
		}

		return $fields;
	}

	private static function makeOptionsForSelect( array $arr ): array {
		$arr = array_filter( $arr );
		$arr = array_combine( $arr, $arr );

		return $arr;
	}

	private static function makeOptionsWithAllForSelect( array $arr ): array {
		return [ 'הכל' => '' ] + self::makeOptionsForSelect( $arr ); // @todo i18n
	}

	private static function getContentAreaOptions(): array {
		return self::makeOptionsWithAllForSelect( ArticleContentArea::getValidContentAreas() );
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}
}
