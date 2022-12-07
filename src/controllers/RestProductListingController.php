<?php

namespace NarrysTech\Api_Rest\controllers;

use NarrysTech\Api_Rest\classes\Helpers;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Hook;
use PrestaShop\PrestaShop\Adapter\Entity\Language;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use PrestaShop\PrestaShop\Adapter\Entity\WebserviceKey;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Search\SearchProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Core\Product\Search\Pagination;
use ProductListingFrontController;

abstract class RestProductListingController extends ProductListingFrontController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [];
    /**
     * Datas to send in ajax
     *
     * @var array
     */
    public $datas = [];
    /**
     * success
     *
     * @var integer
     */
    public $codeSuccess = 200;
    /**
     * Errors method if not found for this route
     *
     * @var integer
     */
    public $codeMethod = 405;
    /**
     * Error internal serveur
     *
     * @var integer
     */
    public $codeServeur = 500;
    /**
     * Page or route if not exists
     *
     * @var integer
     */
    public $codeNotFound = 404;
    /**
     * Errors fields required or type if not correct
     *
     * @var integer
     */
    public $codeErrors = 400;
    /**
     * Error Authenticate
     *
     * @var integer
     */
    public $codeAuthenticate = 401;
    /**
     * Error Authenticate Customer
     *
     * @var integer
     */
    public $codeAuthenticateCustomer = 402;

    public function init()
    {
        header("Content-type: application/json");
        parent::init();

        //Authenticate application with Bearer token
        $this->authenticate();

        //Update current language
        $id_lang = Tools::getValue('id_lang');
        if ($id_lang) {
            $language = new Language((int) $id_lang);
            if (Validate::isLoadedObject($language)) {
                $this->context->language = $language;
            }
        }

        //Check method is submit
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;

            default:
                # code...
                break;
        }
    }

    protected function processGetRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processPostRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processPutRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processDeleteRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function methodNotAllowed()
    {
        $this->ajaxRender(Helpers::response_json([
            "message" => "Method not allowed"
        ], $this->codeMethod, false));
        die;
    }


    /**
     * Undocumented function
     *
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    public function isValideType(string $type, string $value): bool
    {
        switch ($type) {
            case 'text':
                return Validate::isString($value);
                break;
            case 'number':
                return Validate::isInt((int) $value) || Validate::isFloat((float) $value);
                break;
            case 'tel':
                return Validate::isPhoneNumber($value);
                break;
            case 'email':
                return Validate::isEmail($value);
                break;
            case 'file':
                return Validate::isFileName($value);
                break;
            case 'password':
                return Validate::isPlaintextPassword($value);
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function checkErrorsRequiredOrType(): array
    {
        $inputs = array();

        foreach ($this->params['fields'] as $key => $a) {
            //Get Name
            $name = $a['name'];
            //Get Required
            $required = (bool) $a['required'];
            //Get Type
            $type = $a['type'];
            //Get Value
            $value = Tools::getValue($name);

            //Field is required and null
            if (($required === true) && (($value == false || is_null($value)))) {
                $this->errors["required"][] = $a;
            }
            //Field type if not valide
            if ($this->isValideType($type, $value) == false) {
                $this->errors["type"][] = $a;
            }
            //If field is not required and if not submit
            if ($required === false && (($value == false || is_null($value)))) {
                $value = isset($a["default"]) ? $a["default"] : "null";
            }

            $inputs[$name] = $value;
        }

        //If has errors required
        if (isset($this->errors["required"]) && !empty($this->errors["required"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is required!");
            foreach ($this->errors["required"] as $field) {
                $errors["fields"][] = $field["name"];
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        //If has errors type
        if (isset($this->errors["type"]) && !empty($this->errors["type"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is not correct!");
            foreach ($this->errors["type"] as $field) {
                $errors["fields"][$field["name"]] = Tools::getValue($field["name"]);
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        return $inputs;
    }

    public function renderAjax(int $status = 200, bool $success = true)
    {
        $this->ajaxRender(Helpers::response_json($this->datas, $status, $success));
        die;
    }

    public function renderAjaxErrors($message, int $status = null)
    {
        $this->datas = [];
        $this->datas["errors"]["message"] = $message;
        $this->renderAjax($status === null ? $this->codeErrors : $status, false);
    }

    public function authenticate()
    {
        //Check if Bearer Token passing in header
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $token = $matches[1];
            if (WebserviceKey::keyExists($token)) { //If Bearer token exists
                if (!WebserviceKey::isKeyActive($token)) { //If Bearer token if active
                    $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is not active");
                    $this->renderAjax($this->codeAuthenticate, false);
                }
            } else {
                $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is not correct");
                $this->renderAjax($this->codeAuthenticate, false);
            }
        } else {
            $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is empty");
            $this->renderAjax($this->codeAuthenticate, false);
        }
    }

    public function getImage($object, $id_image)
    {
        $retriever = new ImageRetriever($this->context->link);
        return $retriever->getImage($object, $id_image);
    }

    protected function getProductSearchVariables()
    {
        /*
         * To render the page we need to find something (a ProductSearchProviderInterface)
         * that knows how to query products.
         */

        // the search provider will need a context (language, shop...) to do its job
        $context = $this->getProductSearchContext();
        
        // the controller generates the query...
        if (Tools::getValue('s')) {
            $query = $this->getProductSearchQuery();
        } else {
            $query = new ProductSearchQuery();
            $query
                ->setIdCategory(Tools::getValue('id_category'))
                ->setSortOrder(new SortOrder('product', Tools::getProductsOrder('by'), Tools::getProductsOrder('way')));
        }

        // ...modules decide if they can handle it (first one that can is used)
        $provider = $this->getProductSearchProviderFromModules($query);

        // if no module wants to do the query, then the core feature is used
        if (null === $provider) {
            $provider = $this->getDefaultProductSearchProvider();
        }

        $resultsPerPage = (int)Tools::getValue('resultsPerPage');
        if ($resultsPerPage <= 0) {
            $resultsPerPage = Configuration::get('PS_PRODUCTS_PER_PAGE');
        }

        // we need to set a few parameters from back-end preferences
        $query
            ->setResultsPerPage($resultsPerPage)
            ->setPage(max((int)Tools::getValue('page'), 1));

        // set the sort order if provided in the URL
        if (($encodedSortOrder = Tools::getValue('order'))) {
            $query->setSortOrder(SortOrder::newFromString(
                $encodedSortOrder
            ));
        }

        // get the parameters containing the encoded facets from the URL
        $encodedFacets = Tools::getValue('q');

        /*
         * The controller is agnostic of facets.
         * It's up to the search module to use /define them.
         *
         * Facets are encoded in the "q" URL parameter, which is passed
         * to the search provider through the query's "$encodedFacets" property.
         */

        $query->setEncodedFacets($encodedFacets);

        Hook::exec('actionProductSearchProviderRunQueryBefore', [
            'query' => $query,
        ]);

        // We're ready to run the actual query!

        /** @var ProductSearchResult $result */
        $result = $provider->runQuery(
            $context,
            $query
        );

        Hook::exec('actionProductSearchProviderRunQueryAfter', [
            'query' => $query,
            'result' => $result,
        ]);

        if (Configuration::get('PS_CATALOG_MODE') && !Configuration::get('PS_CATALOG_MODE_WITH_PRICES')) {
            $this->disablePriceControls($result);
        }

        // sort order is useful for template,
        // add it if undefined - it should be the same one
        // as for the query anyway
        if (!$result->getCurrentSortOrder()) {
            $result->setCurrentSortOrder($query->getSortOrder());
        }

        // prepare the products
        $products = $result->getProducts();
        // with the core
        $rendered_facets = $this->renderFacets(
            $result
        );
        $rendered_active_filters = $this->renderActiveFilters(
            $result
        );

        $pagination = $this->getTemplateVarPagination(
            $query,
            $result
        );

        // prepare the sort orders
        // note that, again, the product controller is sort-orders
        // agnostic
        // a module can easily add specific sort orders that it needs
        // to support (e.g. sort by "energy efficiency")
        $sort_orders = $this->getTemplateVarSortOrders(
            $result->getAvailableSortOrders(),
            $query->getSortOrder()->toString()
        );

        $sort_selected = false;
        if (!empty($sort_orders)) {
            foreach ($sort_orders as $order) {
                if (isset($order['current']) && true === $order['current']) {
                    $sort_selected = $order['label'];

                    break;
                }
            }
        }

        $searchVariables = [
            'result' => $result,
            'label' => $this->getListingLabel(),
            'products' => $products,
            'sort_orders' => $sort_orders,
            'sort_selected' => $sort_selected,
            'pagination' => $pagination,
            'rendered_facets' => $rendered_facets,
            'rendered_active_filters' => $rendered_active_filters,
            'js_enabled' => $this->ajax,
            'current_url' => $this->updateQueryString([
                'q' => $result->getEncodedFacets(),
            ]),
        ];

        Hook::exec('filterProductSearch', ['searchVariables' => &$searchVariables]);
        Hook::exec('actionProductSearchAfter', $searchVariables);

        return $searchVariables;
    }

    /**
     * Pagination is HARD. We let the core do the heavy lifting from
     * a simple representation of the pagination.
     *
     * Generated URLs will include the page number, obviously,
     * but also the sort order and the "q" (facets) parameters.
     *
     * @param ProductSearchQuery $query
     * @param ProductSearchResult $result
     *
     * @return array An array that makes rendering the pagination very easy
     */
    protected function getTemplateVarPagination(
        ProductSearchQuery $query,
        ProductSearchResult $result
    ) {
        $pagination = new Pagination();
        $pagination
            ->setPage($query->getPage())
            ->setPagesCount(
                (int) ceil($result->getTotalProductsCount() / $query->getResultsPerPage())
            );

        $totalItems = $result->getTotalProductsCount();
        $itemsShownFrom = ($query->getResultsPerPage() * ($query->getPage() - 1)) + 1;
        $itemsShownTo = $query->getResultsPerPage() * $query->getPage();

        $pages = array_map(function ($link) {
            $link['url'] = $this->updateQueryString([
                'page' => $link['page'] > 1 ? $link['page'] : null,
            ]);

            return $link;
        }, $pagination->buildLinks());

        //Filter next/previous link on first/last page
        $pages = array_filter($pages, function ($page) use ($pagination) {
            if ('previous' === $page['type'] && 1 === $pagination->getPage()) {
                return false;
            }
            if ('next' === $page['type'] && $pagination->getPagesCount() === $pagination->getPage()) {
                return false;
            }

            return true;
        });

        $new_pages = [];
        foreach ($pages as $page) {
            $new_pages[] = $page;
        }

        return [
            'total_items' => $totalItems,
            'items_shown_from' => $itemsShownFrom,
            'items_shown_to' => ($itemsShownTo <= $totalItems) ? $itemsShownTo : $totalItems,
            'current_page' => $pagination->getPage(),
            'pages_count' => $pagination->getPagesCount(),
            'pages' => $new_pages,
            // Compare to 3 because there are the next and previous links
            'should_be_displayed' => (count($pagination->buildLinks()) > 3),
        ];
    }

    private function getProductSearchProviderFromModules($query)
    {
        $providers = Hook::exec(
            'productSearchProvider',
            ['query' => $query],
            null,
            true
        );

        if (!is_array($providers)) {
            $providers = [];
        }

        foreach ($providers as $provider) {
            if ($provider instanceof ProductSearchProviderInterface) {
                return $provider;
            }
        }
    }

    protected function getFacets(ProductSearchResult $result)
    {
        $facetCollection = $result->getFacetCollection();
        // not all search providers generate menus
        if (empty($facetCollection)) {
            return '';
        }

        $facetsVar = array_map(
            [$this, 'prepareFacetForTemplate'],
            $facetCollection->getFacets()
        );

        $activeFilters = [];
        foreach ($facetsVar as $facet) {
            foreach ($facet['filters'] as $filter) {
                if ($filter['active']) {
                    $activeFilters[] = $filter;
                }
            }
        }

        return [
            'filters' => $facetCollection,
            'activeFilters' => $activeFilters
        ];
    }

    public function getListingLabel()
    {
        return $this->getTranslator()->trans('Search results', array(), 'Shop.Theme.Catalog');
    }

    /**
     * Gets the product search query for the controller.
     * That is, the minimum contract with which search modules
     * must comply.
     *
     * @return \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery
     */
    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setSortOrder(new SortOrder('product', 'position', 'desc'))
            ->setSearchString($this->search_string)
            ->setSearchTag($this->search_tag);

        return $query;
    }

    /**
     * We cannot assume that modules will handle the query,
     * so we need a default implementation for the search provider.
     *
     * @return \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface
     */
    protected function getDefaultProductSearchProvider()
    {
        return new SearchProductSearchProvider(
            $this->getTranslator()
        );
    }

    /**
     * Renders an array of active filters.
     *
     * @param array $facets
     *
     * @return array the values of the facets
     */
    protected function renderActiveFilters(ProductSearchResult $result)
    {
        $facetCollection = $result->getFacetCollection();
        // not all search providers generate menus
        if (empty($facetCollection)) {
            return '';
        }

        $facetsVar = array_map(
            [$this, 'prepareFacetForTemplate'],
            $facetCollection->getFacets()
        );

        $activeFilters = [];
        foreach ($facetsVar as $facet) {
            foreach ($facet['filters'] as $filter) {
                if ($filter['active']) {
                    $activeFilters[] = $filter;
                }
            }
        }

        return [
            'activeFilters' => $activeFilters,
            'clear_all_link' => $this->updateQueryString(['q' => null, 'page' => null]),
        ];
    }

    /**
     * Renders an array of facets.
     *
     * @param array $facets
     *
     * @return array the values of the facets
     */
    protected function renderFacets(ProductSearchResult $result)
    {
        $facetCollection = $result->getFacetCollection();
        // not all search providers generate menus
        if (empty($facetCollection)) {
            return '';
        }

        $facetsVar = array_map(
            [$this, 'prepareFacetForTemplate'],
            $facetCollection->getFacets()
        );

        $activeFilters = [];
        foreach ($facetsVar as $facet) {
            foreach ($facet['filters'] as $filter) {
                if ($filter['active']) {
                    $activeFilters[] = $filter;
                }
            }
        }

        return [
            'facets' => $facetsVar,
            'js_enabled' => $this->ajax,
            'activeFilters' => $activeFilters,
            'sort_order' => $result->getCurrentSortOrder()->toString(),
            'clear_all_link' => $this->updateQueryString(['q' => null, 'page' => null]),
        ];
    }
}
