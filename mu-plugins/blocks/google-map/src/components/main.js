/**
 * External dependencies
 */
import { throttle } from 'lodash';

/**
 * WordPress dependencies
 */
import { useCallback, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Search from './search';
import Map from './map';
import List from './list';
import { filterMarkers, speakSearchUpdates } from '../utilities/content';
import { getValidMarkers } from '../utilities/google-maps-api';

/**
 * Primary entry point and rendering component for the block.
 *
 * @param {Object}  props
 * @param {string}  props.blockStyle
 * @param {boolean} props.showMap
 * @param {boolean} props.showList
 * @param {boolean} props.showSearch
 * @param {boolean} props.showFilters
 * @param {string}  props.apiKey
 * @param {Array}   props.markers
 * @param {Object}  props.markerIcon
 * @param {string}  props.searchIcon
 * @param {Array}   props.searchFields
 * @param {number}  props.listDisplayLimit
 */
export default function Main( {
	blockStyle,
	showMap,
	showList,
	listDisplayLimit,
	showSearch,
	apiKey,
	markers: rawMarkers,
	markerIcon,
	searchIcon,
	searchFields,
} ) {
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const searchQueryInitialized = useRef( false );

	const [ filters, setFilters ] = useState( {
		format: '',
		type: '',
		month: [],
		country: [],
	} );

	// This probably shouldn't be state because it can be derived from `markers` and `searchQuery`,
	// but it also feels wrong to have the map and list components do the filtering when they could
	// just receive it as props without having to be aware of the business logic. It has to be state
	// somewhere for React to trigger a re-render, though.
	const validMarkers = getValidMarkers( rawMarkers );
	const [ visibleMarkers, setVisibleMarkers ] = useState( validMarkers );

	/**
	 * Update the search state as the user types, and make sure the map/list are visible.
	 */
	const onQueryChange = useCallback( ( event ) => {
		setSearchQuery( event.target.value );

		/*
		 * Sometimes the map may be taking up most of the viewport, so the user won't see the list changing as
		 * they type their query. This helps direct them to the results.
		 */
		event.target.scrollIntoView( {
			inline: 'start',
			behavior: 'smooth',
		} );

		// This is passing the value directly, instead of using state, because the throttled function only has
		// access to the version of state that existed at the time the function was created. Updating the
		// function with something like `useCallback` is more complicated than you'd think.
		throttledRedrawMap( event.target.value );
	}, [] );

	const onFilterChange = useCallback( ( event ) => {
		console.log( event );

		// setFilters( {} ); // gotta merge the new value into current state

		// call redrawmap? would need to refactor it for filters in addition to search. will want speaking and setvismarkers though
		// wont need debounce b/c not as fast as typing
	} );

	/**
	 * Redraw the map and list based on the user's search query.
	 */
	const redrawMap = useCallback(
		( newSearchQuery ) => {
			const filteredMarkers = filterMarkers( validMarkers, newSearchQuery, searchFields );

			setVisibleMarkers( filteredMarkers );

			// Avoid speaking on the initial page load.
			if ( ! searchQueryInitialized.current ) {
				searchQueryInitialized.current = true;
				return;
			}

			speakSearchUpdates( newSearchQuery, filteredMarkers.length );
		},
		[ validMarkers, searchFields ]
	);

	/**
	 * Throttle the redraw function so it doesn't do expensive operations that the user won't notice.
	 */
	const throttledRedrawMap = useMemo( () => {
		return throttle( redrawMap, 200, {
			leading: false,
			trailing: true,
		} );
	}, [ redrawMap ] );

	return (
		<>
			{ showSearch && (
				<Search searchQuery={ searchQuery } onQueryChange={ onQueryChange } iconURL={ searchIcon } />
			) }

			{ showMap && (
				<Map apiKey={ apiKey } markers={ visibleMarkers } icon={ markerIcon } blockStyle={ blockStyle } />
			) }

			{ showList && visibleMarkers.length > 0 && (
				<List markers={ visibleMarkers } displayLimit={ listDisplayLimit } />
			) }

			{ visibleMarkers.length === 0 && searchQuery.length > 0 && (
				<p className="wporg-marker-list__container">
					{
						// Translators: %s is the search query.
						sprintf( __( 'No events were found matching %s.', 'wporg' ), searchQuery )
					}
				</p>
			) }
		</>
	);
}
