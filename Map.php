<?php
namespace releaznl\markerclusterer;

use dosamigos\google\maps\ObjectAbstract;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

/**
 * Class Map
 * @package edofre\markerclusterer
 */
class Map extends \dosamigos\google\maps\Map
{
	/**
	 * @var array stores javascript code that is going to be rendered together with script initialization
	 */
	private $_js = [];

	/**
	 * @return string
	 */
	public function getJs()
	{
		$name = $this->getName();
		$width = strpos($this->width, "%") ? $this->width : $this->width . 'px';
		$height = strpos($this->height, "%") ? $this->height : $this->height . 'px';
		$containerId = ArrayHelper::getValue($this->containerOptions, 'id', $name . '-map-canvas');
		$overlaysJs = [];
		$js = [];
		foreach ($this->getOverlays() as $overlay) {
			/** @var ObjectAbstract $overlay */
			if (!ArrayHelper::keyExists("{$name}infoWindow", $this->getClosureScopedVariables()) &&
				method_exists($overlay, 'getIsInfoWindowShared')
				&& $overlay->getIsInfoWindowShared()
			) {

				$this->setClosureScopedVariable("{$name}infoWindow");
				$this->appendScript("{$name}infoWindow = new google.maps.InfoWindow();");
			}
			$overlay->options['map'] = new JsExpression($this->getName());
			$overlaysJs[] = $overlay->getJs();
		}
		$js[] = "(function(){";
		$js[] = $this->getClosureScopedVariablesScript();
		$js[] = "function initialize(){";
		$js[] = "var mapOptions = {$this->getEncodedOptions()};";
		$js[] = "var markers = [];";
		$js[] = "var container = document.getElementById('{$containerId}');";
		$js[] = "container.style.width = '{$width}';";
		$js[] = "container.style.height = '{$height}';";
		$js[] = "var {$this->getName()} = new google.maps.Map(container, mapOptions);";
		$js = ArrayHelper::merge($js, $overlaysJs);
		// Make object available globally
		$js[] = "window.globalmap = {$this->getName()};";
		foreach ($this->events as $event) {
			/** @var Event $event */
			$js[] = $event->getJs($name);
		}

		foreach ($this->getPlugins()->getInstalledPlugins() as $plugin) {
			/** @var \dosamigos\google\maps\PluginAbstract $plugin */
			$plugin->map = $this->getName();
			$js[] = $plugin->getJs($name);
		}

		$js = ArrayHelper::merge($js, $this->_js);

		// Register the ClustererAsset
		$view = \Yii::$app->getView();
		$cluster_asset_manager = ClustererAsset::register($view);
		// Create the MarkerClusterer object and add the markers
		$js[] = "var markerCluster = new MarkerClusterer({$name}, markers, {
			imagePath: '{$cluster_asset_manager->imagePath}'
		});";
		
		// Make markerCluster available globally
		$js[] = "window.globalmarkerCluster = markerCluster;";
		
		$js[] = "};";
		$js[] = "google.maps.event.addDomListener(window, 'load', initialize);";
		$js[] = "})();";

		return implode("\n", $js);
	}

	/**
	 * @param $js
	 *
	 * @return $this
	 */
	public function appendScript($js)
	{
		$this->_js[] = $js;

		return $this;
	}

}
