<?php
/**
 * This method is an improved version of PHP's filter_input() for
 * sanitizing input data from various sources.
 *
 * @param int    $type          One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
 * @param string $variable_name Name of a variable to get.
 * @param int    $filter        The ID of the filter to apply.
 * @param mixed  $options       filter to apply.
 *
 * @return mixed
 */
function revision_buster_filter_input( $type, $variable_name, $filter = FILTER_DEFAULT, $options = 0 ) {
    switch ( $filter ) {
        case RB_FILTER_SANITIZE_STRING:
            $sanitized_variable = sanitize_text_field( filter_input( $type, $variable_name, FILTER_UNSAFE_RAW, $options ) );
            break;
        default:
            $sanitized_variable = filter_input( $type, $variable_name, $filter, $options );
            break;
    }

    return $sanitized_variable;
}