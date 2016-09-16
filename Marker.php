<?php
namespace releaznl\markerclusterer;

/**
 * Class Marker
 * @package edofre\markerclusterer
 */
class Marker extends \dosamigos\google\maps\overlays\Marker
{
	public $modelId;
	/**
	 * The constructor js code for the Marker object
	 * @return string
	 */
	public function getJs()
	{
		$js = $this->getInfoWindowJs();
		
		if(isset($this->modelId)){
			$this->options['modelId'] = $this->modelId;
		}
		
		$js[] = "var {$this->getName()} = new google.maps.Marker({$this->getEncodedOptions()});";
		// add the marker to markers array
		$js[] = "markers.push({$this->getName()});";
		
		// Make markers available globally
		$js[] = "window.globalmarkers = markers;";

		foreach ($this->events as $event) {
			/** @var \dosamigos\google\maps\Event $event */
			$js[] = $event->getJs($this->getName());
		}

		return implode("\n", $js);
	}
}