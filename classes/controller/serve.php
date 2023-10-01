<?php

namespace Resources;

class Controller_Serve extends \Controller
{
	
	public function action_index()
	{
		// Read resources from root configuration file
		$resources = \Config::load('resources');

		// Read extension (removed by default) and path from route parameter
		$extension = $this->request->input()->extension();
		$path = $this->param('path') . '.' . $extension;

		// Check if path targets module
		$resolvedPath = '';
		if ($moduleResources = $this->module_resources($path)) {
			// Find resource in module
			$resolvedPath = $this->resolve_path(
				substr($path, strpos($path, '/') + 1),
				$moduleResources,
				'modules' . DS . substr($path, 0, strpos($path, '/')) . DS,
			);
		} else {
			// Find resource in app
			$resolvedPath = $this->resolve_path($path, $resources);
		}

		if ($resolvedPath) {
			// Convert extension to MIME type, using external library
			// @see https://github.com/ralouphie/mimey
			$mimes = new \Mimey\MimeTypes;

			// Send file, with correct content type
			return \Response::forge(
				file_get_contents($resolvedPath),
				200,
				[
					// 'Content-Type' => mime_content_type($filesystemPath),
					'Content-Type' => $mimes->getMimeType($extension),
				]
			);
		}

		// Resource not found
		return \Response::forge('', 404);
	}

	protected function module_resources($path)
	{
		// Break path into segments
		$pathSegments = explode('/', $path);

		// Check if 1st segment matches module
		if (\Module::exists($pathSegments[0])) {
			$module = $pathSegments[0];

			// Load module
			if (!\Module::loaded($module)) {
				\Module::load($module);
			}

			// Read configuration from module
			return \Config::load("{$module}::resources");
		}

		return array();
	}

	protected function resolve_path($path, $resources, $prefix = '') {
		if (! $resources) {
			// No resources defined
			return '';
		}

		$pathPrefix = APPPATH . $prefix;
		foreach ($resources as $regex => $resolved) {
			if (preg_match("/{$regex}/", $path, $matches)) {
				if (count($matches) > 1) {
					// Captured group(s), attempt replacement
					$resolvedPath = preg_replace("/{$regex}/", $resolved, $path);
				} else {
					// No group, therefore no replacements
					$resolvedPath = $resolved;
				}

				// Check if resolved path actually exists, using 'realpath'
				if ($resolvedPath && ($realPath = realpath($pathPrefix . $resolvedPath))) {
					// Return valid resource file
					return $realPath;
				}
			}
		}

		// No resource found
		return '';
	}

}		