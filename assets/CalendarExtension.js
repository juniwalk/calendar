
/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

class CalendarExtension
{
	#control = null;
	#options = {};
	#config = {};
	#view = null;

	#calendar = null;

	constructor(element, config, options = {}) {
		this.#control = element;
		this.#options = options;
		this.#config = config;
	}

	initialize(naja) {
		if (typeof FullCalendar === 'undefined') {
			console.log('Missing FullCalendar component');
			return;
		}

		naja.snippetHandler.addEventListener('afterUpdate', (event) => this.#attach(event.detail.snippet));
		naja.addEventListener('complete', () => this.#refetch());

		this.#attach(document);
	}

	#attach(snippet) {
		if (!snippet.contains(this.#control)) {
			return;
		}

		let element = this.#control.querySelector('#'+this.#prefix('timeGrid'));

		this.#calendar = new FullCalendar.Calendar(element, Object.assign(this.#config, {
			eventSources: this.#options.sources,
			timeZoneParam: this.#prefix('timeZone'),
			startParam: this.#prefix('start'),
			endParam: this.#prefix('end'),

			// Base events
			windowResize: (info) => this.#handleResize(info.view, element),
			datesSet: (info) => this.#handleDatesSet(info),

			// Event events
			eventsSet: () => { naja.uiHandler.bindUI(element) },
			eventDidMount: (info) => this.#handleEventMount(info),
			eventWillUnmount: (info) => this.#handleEventUnmount(info),

			eventDragStart:		() => element.toggleAttribute('data-no-refresh'),
			eventDragStop:		() => element.toggleAttribute('data-no-refresh'),
			eventResizeStart:	() => element.toggleAttribute('data-no-refresh'),
			eventResizeStop:	() => element.toggleAttribute('data-no-refresh'),

			// Request events
			dateClick: (event) => this.#httpRequest(this.#options.dateClick, {
				allDay: Number(event.allDay),
				start: event.dateStr,
			}),

			eventDrop: ({event}) => this.#httpRequest(this.#options.eventDrop, {
				type: event.extendedProps.source,
				allDay: Number(event.allDay),
				start: event.startStr,
				id: event.id,
			}),

			eventResize: ({event}) => this.#httpRequest(this.#options.eventResize, {
				type: event.extendedProps.source,
				allDay: Number(event.allDay),
				end: event.endStr,
				id: event.id,
			}),
		}));

		this.#calendar.render();

		setInterval(() => this.#handleRefresh(element), this.#options.autoRefresh);

		window.dispatchEvent(new Event('resize'));


		// Toolbar actions
		this.#control.querySelector('[data-prev]').addEventListener('click', () => this.#switchPrev());
		this.#control.querySelector('[data-next]').addEventListener('click', () => this.#switchNext());
		this.#control.querySelector('[data-today]').addEventListener('click', () => this.#switchToday());
		this.#control.querySelectorAll('[data-view]').forEach((element) => {
			element.addEventListener('click', (event) => this.#switchView(element.dataset.view));
		});

		// Settings dropdown
		this.#control.querySelector('[data-set-show-details]').addEventListener('click',	(event) => this.#toggleDetails(event));
		this.#control.querySelector('[data-set-auto-refresh]').addEventListener('click',	(event) => this.#toggleAutoRefresh(event));
		this.#control.querySelector('[data-set-editable]').addEventListener('click', 		(event) => this.#toggleEditable(event));
		this.#control.querySelector('[data-set-responsive]').addEventListener('click',		(event) => this.#toggleResponsive(event));
	}


	#switchPrev() {
		this.#calendar.prev();
		this.#httpCookie('date', this.#calendar.view.currentStart.toISOString(), {expires: .1});
	}

	#switchNext() {
		this.#calendar.next();
		this.#httpCookie('date', this.#calendar.view.currentStart.toISOString(), {expires: .1});
	}

	#switchToday() {
		this.#calendar.today();
		this.#httpCookie('date', null);
	}

	#switchView(view) {
		this.#control.dataset.view = null;
		this.#calendar.changeView(view);
		this.#httpCookie('view', view, {expires: 365});
	}


	#toggleDetails(event) {
		let element = this.#control.querySelector('#'+this.#prefix('timeGrid'));
		let isShowDetails = element.toggleAttribute('data-details');

		event.currentTarget.querySelector('.icon i').classList.toggle('invisible');

		this.#httpCookie('showDetails', isShowDetails ? 1 : 0, {expires: 365});

		event.preventDefault();
		event.stopPropagation();
	}

	#toggleEditable(event) {
		let isEditable = !this.#calendar.getOption('editable');
		this.#calendar.setOption('editable', isEditable);

		event.currentTarget.querySelector('.icon i').classList.toggle('invisible');

		this.#httpCookie('editable', isEditable ? 1 : 0, {expires: 365});

		event.preventDefault();
		event.stopPropagation();
	}

	#toggleAutoRefresh(event) {
		let element = this.#control.querySelector('#'+this.#prefix('timeGrid'));
		let isAutoRefresh = element.dataset.refresh === "true";

		element.dataset.refresh = isAutoRefresh ? "false" : "true";
		element.removeAttribute('data-no-refresh');

		event.currentTarget.querySelector('.icon i').classList.toggle('invisible');

		this.#httpCookie('autoRefresh', isAutoRefresh ? 0 : 1, {expires: 365});
		this.#handleRefresh(element);

		event.preventDefault();
		event.stopPropagation();
	}

	#toggleResponsive(event) {
		let element = this.#control.querySelector('#'+this.#prefix('timeGrid'));
		let isResponsive = element.dataset.responsive === "true";

		element.dataset.responsive = isResponsive ? "false" : "true";

		event.currentTarget.querySelector('.icon i').classList.toggle('invisible');

		this.#httpCookie('responsive', isResponsive ? 0 : 1, {expires: 365});

		event.preventDefault();
		event.stopPropagation();
	}


	#handleRefresh(element) {
		let isNoRefresh = (element.dataset.noRefresh !== undefined);
		let isAutoRefresh = (element.dataset.refresh === "true");

		if (isAutoRefresh && !isNoRefresh) {
			this.#refetch();
		}
	}

	#handleEventUnmount(info) {
		bootstrap.Popover.getOrCreateInstance(info.el)?.dispose();
	}

	#handleEventMount(info) {
		let {label, content, source} = info.event.extendedProps;
		let {el, event} = info;

		el.id = 'event-'+source+'-'+event.id;

		// ? Scroll to Event when hash in Url
		if (location.hash === '#'+el.id) {
			el.classList.add('focus', 'scale-slow');

			if (!config.isLoaded) {
				el.scrollIntoView({ behavior: 'smooth' });
				config.isLoaded = true;
			}
		}

		let group = this.#control.querySelectorAll('.fc-group-'+event.groupId);

		el.addEventListener('mouseenter', () => group.forEach((event) => event.classList.add('hover')));
		el.addEventListener('mouseleave', () => group.forEach((event) => event.classList.remove('hover')));

		if (!label || !content) {
			return;
		}

		let element = this.#control.querySelector('#'+this.#prefix('timeGrid'));
		let popover = new bootstrap.Popover(el, {
			title: label,
			content: content,
			placement: 'right',
			trigger: 'hover',
			html: true,
		});

		el.addEventListener('show.bs.popover', (event) => {
			let isNoRefresh = (element.dataset.noRefresh !== undefined);
			let isShowDetails = (element.dataset.details !== undefined);

			if (isNoRefresh || !isShowDetails) {
				event.preventDefault();
			}
		});
	}

	#handleDatesSet({view, start, end}) {
		const today = new Date;
		const isToday = today >= start && today < end;

		this.#control.querySelector('[data-today]').classList.toggle('disabled', isToday);
		this.#control.querySelectorAll('[data-view]').forEach((element) => {
			element.classList.remove('active');
		});

		this.#control.querySelector('[data-view='+ view.type +']').classList.add('active');
		this.#control.querySelector('[data-title]').innerHTML = view.title;

		const activeView = this.#control.querySelector('[data-view].active');
		this.#control.querySelectorAll('[data-view-name]').forEach((element) => {
			element.innerHTML = activeView.innerHTML;
		});

		this.#control.querySelectorAll('[data-signal-date]').forEach((target) => {
			let url = new URL(target.href);
			let prefix = '';

			if (url.searchParams.has('do')) {
				prefix = url.searchParams.get('do');
				prefix = prefix.substring(0, prefix.lastIndexOf('-') +1);
			}

			url.searchParams.set(prefix+'start', start.toISOString());
			url.searchParams.set(prefix+'end', end.toISOString());

			target.href = url;
		});
	}

	#handleResize(view, element) {
		let isLandscape = window.matchMedia('(orientation: landscape)').matches;
		let isResponsive = element.dataset.responsive === 'true';
		let windowWidth = window.innerWidth;
		let targetWidth = 800;

		if (!isResponsive) {
			return;
		}

		if (!isLandscape && windowWidth < targetWidth && this.#view == null) {
			this.#calendar.changeView('timeGridDay');
			this.#view = view.type;
			return;
		}

		if (!this.#view || (!isLandscape && windowWidth < targetWidth && this.#view !== null)) {
			return;
		}

		this.#calendar.changeView(this.#view);
		this.#view = null;
	}


	#refetch() {
		let dayGridBody = document.querySelector('.fc-daygrid-body');
		dayGridBody.style.minHeight = getComputedStyle(dayGridBody).height;

		this.#calendar?.refetchEvents();

		dayGridBody.style.removeProperty('min-height');
	}

	#httpRequest(url, params = {}) {
		return naja.makeRequest('GET', url, this.#prefixObj(params));
	}

	#httpCookie(name, value, params = {}) {
		name = this.#prefix(name);

		if (value === null) {
			Cookies.remove(name);
			return;
		}

		Cookies.set(name, value, Object.assign(params, {
			sameSite: 'strict',
			secure: true,
			path: '/',
		}));
	}

	#prefix(key) {
		return this.#control.id + "-" + key;
	}

	#prefixObj(obj) {
		let prefixed = Object.entries(obj).map(([k, v]) => [this.#prefix(k), v]);
		return Object.fromEntries(prefixed);
	}
}
