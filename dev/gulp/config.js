module.exports = {
	paths: {
		config: {
			system: 'config/system.json'
		},
		dist: {
			images: {
				admin: 'public/assets/imgs/incc/',
				web: 'public/assets/imgs/'
			},
			js: {
				admin: 'public/assets/js/incc/',
				web: 'public/assets/js/'
			},
			sass: {
				admin: 'public/assets/css/incc/',
				web: 'public/assets/css/'
			}
		},
		src: {
			images: {
				admin: 'assets/images/inadmin/',
				web: 'assets/images/web/'
			},
			js: {
				admin: 'assets/js/inadmin/',
				all: 'assets/js/',
				shared: 'assets/js/shared/',
				web: 'assets/js/web/'
			},
			sass: { 
				admin: 'assets/scss/inadmin/',
				web: 'assets/scss/web/',
				all: 'assets/scss/'
			}
		}
	}
};