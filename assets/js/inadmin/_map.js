window.Inachis.Map = {
	key: null,
	showGeoLocation: false,
	retina: false,
	targetMap: null,

	_init: function ()
	{
		this.showGeoLocation = location.protocol === 'https:' && 'geolocation' in navigator;
		$('.ui-map').each($.proxy(this._initMap, this));
		$(document).on('click', '.ui-cross', $.proxy(this.clear, this));
		$(document).on('click', '.ui-geo', $.proxy(this.getGeoLocation, this));
	},

	_initMap: function (index, mapElement)
	{
		if (this.key === null) {
			this.key = $(mapElement).attr('data-google-key')
		}
		mapElement.type = 'hidden';
		var mapName = (mapElement.name || mapElement.id);
		var mapBox = $('<div class="mapbox" id="' + mapName + '__map"></div>');
		$(mapElement).after(mapBox);

		this.addMap($(mapElement), mapName, mapBox);

		var searchBox = $('<label class="material-icons" for="' + mapName + '__search"><span>search<span></label><input class="search" id="' + mapName + '__search" placeholder="Search for location…" type="search" />');
		$(mapBox).after(searchBox);
		$(document).on('keyup', '#' + mapName + '__search', $.proxy(this.search, this));

		if (this.showGeoLocation) {
			var getGeoButton = $('<a href="#" class="material-icons ui-geo" data-map-element="' + mapName + '">my_location</a>');
			$(mapBox).after(getGeoButton);
		}
	},

	addMap: function (mapElement, mapName, mapBox)
	{
		if (mapElement.val() !== '') {
			var mapClear = $('<a class="material-icons ui-cross" href="#">clear</a>');
			$(mapElement).after(mapClear);
			$(mapBox).empty();
			$(mapBox).append($('<img src="' + window.Inachis.Map._generateGoogleMapsImage(mapName, mapElement) + '" />'));
		} else {
			this.addNoMapMessage(mapBox);
		}
	},

	addNoMapMessage: function (mapBox)
	{
		$(mapBox).empty();
		$(mapBox).append($('<p>This content doesn\'t appear on the map! Search for a location to add it.</p>'));
	},

	updateMap: function (mapElement, mapName, mapBox)
	{
		if (mapElement.val() !== '') {
			$(mapBox).find('img').attr('src', window.Inachis.Map._generateGoogleMapsImage(mapName, mapElement));
		}
	},

	addUpdateMap: function (mapElement, mapName, mapBox)
	{
		if (mapElement.val() === '') {
			return;
		}
		if ($(mapBox ).has('img').length === 0) {
			return this.addMap(mapElement, mapName, mapBox);
		}
		this.updateMap(mapElement, mapName, mapBox);
	},

	search: function (event)
	{
		if (event.keyCode !== 13) {
			return;
		}
		event.preventDefault();
		this.targetMap = event.target.id.replace(/__search/, '');
		this.getGoogleGeocode(event.target.value);
		return false;
	},

	clear: function (event)
	{
		event.preventDefault();
		$(event.currentTarget).prev().val('');
		this.addNoMapMessage($('#' + $(event.currentTarget).prev().attr('id') + '__map'));
		$(event.currentTarget).remove();
	},

	_generateGoogleMapsImage: function (mapName, mapElement)
	{
		var baseUri = 'https://maps.googleapis.com/maps/api/staticmap?',
			size = this._getMapDimensionsAsString('#' + mapName + '__map'),
			center = mapElement.val(),
			zoom = 15,
			key = this.key;
		baseUri += this.retina ? 'scale=2&' : '';
		return baseUri + 'center=' + center + '&zoom=' + zoom + '&size=' + size + '&markers=' + mapElement.val() + '&key=' + key;
	},

	_getMapDimensions: function (mapElement)
	{
		return [ $(mapElement).innerWidth(), $(mapElement).innerHeight() ];
	},

	_getMapDimensionsAsString: function (mapElement)
	{
		return $(mapElement).innerWidth() + 'x' + $(mapElement).innerHeight();
	},

	getGoogleGeocode: function (location)
	{
		var baseUri = 'https://maps.googleapis.com/maps/api/geocode/json?';
		$.ajax({
			url: baseUri + 'address=' + location + '&key=' + this.key,

		}).done($.proxy(this._processGeocodeResult, this));
	},

	_processGeocodeResult: function (result)
	{
		if (result.status !== 'OK') {
			window.Inachis._log(result.status + ': ' + result.error_message);
			return;
		}
		var $targetMap = $('#' + this.targetMap);
		$targetMap.attr('value', result.results[0].geometry.location.lat + ',' + result.results[0].geometry.location.lng);
		this.addUpdateMap($targetMap, this.targetMap, $('#' + this.targetMap + '__map'));
	},

	getGeoLocation: function (event)
	{
		event.preventDefault();

		navigator.geolocation.getCurrentPosition($.proxy(function (position)
		{
			var mapName = $(event.currentTarget).attr('data-map-element');
			var mapElement = $('#' + mapName);
			mapName.val(position.coords.latitude + ',' + position.coords.longitude);
			this.addUpdateMap(mapElement, mapName, $(mapName + '__map'));
		}, event));
	}
};

$(document).ready(function () {
	window.Inachis.Map._init();
});
