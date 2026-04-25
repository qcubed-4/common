<?php

    namespace QCubed;

    /**
     * The Router class is responsible for managing and resolving routes based on provided frontend link data.
     */
    class Router
    {
        private array $routes;

        /**
         * Constructor method for initializing routes based on the provided frontend links.
         *
         * @param array $frontendLinks An array of objects containing frontend link data,
         *                              where each object includes properties such as FrontendTitleSlug,
         *                              FrontendClassName, FrontendTemplatePath, ContentTypesManagamentId,
         *                              LinkedId, and GroupedId.
         *
         * @return void
         */
        public function __construct(array $frontendLinks)
        {
            foreach ($frontendLinks as $link) {
                $this->routes[$link->FrontendTitleSlug] = [
                    'class' => $link->FrontendClassName,
                    'tpl' => $link->FrontendTemplatePath,
                    'managament_id' => $link->ContentTypesManagamentId,
                    'linked_id' => $link->LinkedId,
                    'grouped_id' => $link->GroupedId
                ];
            }
        }

        /**
         * Resolves a given path and retrieves the corresponding route information.
         *
         * @param string $path The path to be resolved, used as a key to fetch route information from defined routes.
         *
         * @return array|null Returns an array containing route details if the path exists in the defined routes,
         *                    or null if the path does not exist.
         */
        public function resolve(string $path): ?array
        {
            if (isset($this->routes[$path])) {
                return $this->routes[$path];
            }
            return null;
        }
    }