window.Inachis.Map = {
	key: null,
	showGeoLocation: false,
	retina: false,
	targetMap: null,

	_init() {
		this.showGeoLocation = location.protocol === 'https:' && 'geolocation' in navigator;
		$('.ui-map').each((i, el) => this._initMap(i, el));
		$(document).on('click', '.ui-cross', (e) => this.clear(e));
		$(document).on('click', '.ui-geo', (e) => this.getGeoLocation(e));
	},

	_initMap(index, mapElement) {
		if (this.key === null) {
			this.key = $(mapElement).attr('data-google-key')
		}
		mapElement.type = 'hidden';
		const mapName = (mapElement.name || mapElement.id);
		const mapBox = $(`<div class="mapbox" id="${mapName}__map"></div>`);
		$(mapElement).after(mapBox);

		this.addMap($(mapElement), mapName, mapBox);

		const searchBox = $(`<label class="material-icons" for="${mapName}__search"><span>search<span></label><input class="search" id="${mapName}__search" placeholder="Search for location…" type="search" />`);
		$(mapBox).after(searchBox);
		$(document).on('keyup', `#${mapName}__search`, (e) => this.search(e));

		if (this.showGeoLocation) {
			const getGeoButton = $(`<a href="#" class="material-icons ui-geo" data-map-element="${mapName}">my_location</a>`);
			$(mapBox).after(getGeoButton);
		}
	},

	addMap(mapElement, mapName, mapBox) {
		if (mapElement.val() !== '') {
			const mapClear = $('<a class="material-icons ui-cross" href="#">clear</a>');
			$(mapElement).after(mapClear);
			$(mapBox).empty();
			$(mapBox).append($(`<img src="${window.Inachis.Map._generateGoogleMapsImage(mapName, mapElement)}" />`));
		} else {
			this.addNoMapMessage(mapBox);
		}
	},

	addNoMapMessage(mapBox) {
		$(mapBox).empty();
		$(mapBox).append($('<p>This content doesn\'t appear on the map! Search for a location to add it.</p>'));
	},

	updateMap(mapElement, mapName, mapBox) {
		if (mapElement.val() !== '') {
			$(mapBox).find('img').attr('src', window.Inachis.Map._generateGoogleMapsImage(mapName, mapElement));
		}
	},

	addUpdateMap(mapElement, mapName, mapBox) {
		if (mapElement.val() === '') {
			return;
		}
		if ($(mapBox).has('img').length === 0) {
			return this.addMap(mapElement, mapName, mapBox);
		}
		this.updateMap(mapElement, mapName, mapBox);
	},

	search(event) {
		if (event.keyCode !== 13) {
			return;
		}
		event.preventDefault();
		this.targetMap = event.target.id.replace(/__search/, '');
		this.getGoogleGeocode(event.target.value);
		return false;
	},

	clear(event) {
		event.preventDefault();
		$(event.currentTarget).prev().val('');
		this.addNoMapMessage($(`#${$(event.currentTarget).prev().attr('id')}__map`));
		$(event.currentTarget).remove();
	},

	_generateGoogleMapsImage(mapName, mapElement) {
		let baseUri = 'https://maps.googleapis.com/maps/api/staticmap?';
		const size = this._getMapDimensionsAsString(`#${mapName}__map`);
		const center = mapElement.val();
		const zoom = 15;
		const key = this.key;
		baseUri += this.retina ? 'scale=2&' : '';
		return `${baseUri}center=${center}&zoom=${zoom}&size=${size}&markers=${mapElement.val()}&key=${key}`;
	},

	_getMapDimensions(mapElement) {
		return [$(mapElement).innerWidth(), $(mapElement).innerHeight()];
	},

	_getMapDimensionsAsString(mapElement) {
		return `${$(mapElement).innerWidth()}x${$(mapElement).innerHeight()}`;
	},

	getGoogleGeocode(location) {
		const baseUri = 'https://maps.googleapis.com/maps/api/geocode/json?';
		$.ajax({
			url: `${baseUri}address=${location}&key=${this.key}`,

		}).done((result) => this._processGeocodeResult(result));
	},

	_processGeocodeResult(result) {
		if (result.status !== 'OK') {
			window.Inachis._log(`${result.status}: ${result.error_message}`);
			return;
		}
		const $targetMap = $(`#${this.targetMap}`);
		$targetMap.attr('value', `${result.results[0].geometry.location.lat},${result.results[0].geometry.location.lng}`);
		this.addUpdateMap($targetMap, this.targetMap, $(`#${this.targetMap}__map`));
	},

	getGeoLocation(event) {
		event.preventDefault();

		navigator.geolocation.getCurrentPosition((position) => {
			const mapName = $(event.currentTarget).attr('data-map-element');
			const mapElement = $(`#${mapName}`);
			mapName.val(`${position.coords.latitude},${position.coords.longitude}`);
			this.addUpdateMap(mapElement, mapName, $(`${mapName}__map`));
		});
	}
};

$(document).ready(() => {
	window.Inachis.Map._init();
});
