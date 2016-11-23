<?php 
namespace SmileScreen\Base;

/**
 * ModelStates A simple to keep track of posible model states
 *
 * @package \SmileScreen\Base;
 * @author Willmar Kniker aka DrNotThatEvil <wil@wilv.in>
 * @version 0.0.1
 */
abstract class ModelStates {

    const NOT_SAVED     = 0b00000001;
 
    const FROM_DATABASE = 0b00000010;

}
