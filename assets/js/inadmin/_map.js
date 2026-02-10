window.Inachis.Map = {
	key: null,
	showGeoLocation: false,
	retina: false,
	targetMap: null,

	init() {
		this.showGeoLocation = location.protocol === 'https:' && 'geolocation' in navigator;
		document.querySelectorAll('.ui-map').forEach((el) => this._initMap(el));
		document.querySelectorAll('.ui-cross').forEach((el) => el.addEventListener('click', (e) => this.clear(e)));
		document.querySelectorAll('.ui-geo').forEach((el) => el.addEventListener('click', (e) => this.getGeoLocation(e)));
	},

	_initMap(mapElement) {
		if (this.key === null) {
			this.key = mapElement.dataset.googleKey;
		}
		mapElement.type = 'hidden';
		const mapName = (mapElement.name || mapElement.id);
		const mapBox = document.createElement('div');
		mapBox.className = 'mapbox';
		mapBox.id = `${mapName}__map`;
		mapElement.after(mapBox);

		this.addMap(mapElement, mapName, mapBox);

		const searchBox = document.createElement('label');
		searchBox.className = 'material-icons';
		searchBox.htmlFor = `${mapName}__search`;
		searchBox.innerHTML = '<span>search<span>';
		mapBox.after(searchBox);
		document.getElementById(`#${mapName}__search`).addEventListener('keyup', this.search);

		if (this.showGeoLocation) {
			const getGeoButton = document.createElement('a');
			getGeoButton.className = 'material-icons ui-geo';
			getGeoButton.dataset.mapElement = mapName;
			getGeoButton.innerHTML = 'my_location';
			mapBox.after(getGeoButton);
		}
	},

	addMap(mapElement, mapName, mapBox) {
		if (mapElement.val() !== '') {
			const mapClear = document.createElement('a');
			mapClear.className = 'material-icons ui-cross';
			mapClear.href = '#';
			mapClear.innerHTML = 'clear';
			mapElement.after(mapClear);
			mapBox.innerHTML = `<img src="${window.Inachis.Map._generateGoogleMapsImage(mapName, mapElement)}" />`;
		} else {
			this.addNoMapMessage(mapBox);
		}
	},

	addNoMapMessage(mapBox) {
		mapBox.innerHTML = '<p>This content doesn\'t appear on the map! Search for a location to add it.</p>';
	},

	updateMap(mapElement, mapName, mapBox) {
		if (mapElement.val() !== '') {
			mapBox.querySelector('img').src = window.Inachis.Map._generateGoogleMapsImage(mapName, mapElement);
		}
	},

	addUpdateMap(mapElement, mapName, mapBox) {
		if (mapElement.val() === '') {
			return;
		}
		if (mapBox.querySelector('img') === null) {
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
		const mapElement = event.currentTarget.previousElementSibling;
		mapElement.value = '';
		this.addNoMapMessage(document.getElementById(`${mapElement.id}__map`));
		event.currentTarget.remove();
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
		return [document.getElementById(mapElement).innerWidth(), document.getElementById(mapElement).innerHeight()];
	},

	_getMapDimensionsAsString(mapElement) {
		return `${document.getElementById(mapElement).innerWidth()}x${document.getElementById(mapElement).innerHeight()}`;
	},

	getGoogleGeocode(location) {
		const baseUri = 'https://maps.googleapis.com/maps/api/geocode/json?';
		const url = `${baseUri}address=${location}&key=${this.key}`;
		fetch(url)
			.then(response => response.json())
			.then(result => this._processGeocodeResult(result));
	},

	_processGeocodeResult(result) {
		if (result.status !== 'OK') {
			window.Inachis._log(`${result.status}: ${result.error_message}`);
			return;
		}
		const targetMap = document.getElementById(this.targetMap);
		targetMap.value = `${result.results[0].geometry.location.lat},${result.results[0].geometry.location.lng}`;
		this.addUpdateMap(targetMap, this.targetMap, document.getElementById(`${this.targetMap}__map`));
	},

	getGeoLocation(event) {
		event.preventDefault();

		navigator.geolocation.getCurrentPosition((position) => {
			const mapName = event.currentTarget.dataset.mapElement;
			const mapElement = document.getElementById(mapName);
			mapElement.value = `${position.coords.latitude},${position.coords.longitude}`;
			this.addUpdateMap(mapElement, mapName, document.getElementById(`${mapName}__map`));
		});
	}
};

document.addEventListener('DOMContentLoaded', () => {
	window.Inachis.Map.init();
});
