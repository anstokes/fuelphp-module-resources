<?php

namespace Example;

class Controller_Example extends \Controller
{
	
	public function action_index()
	{
		return \Response::forge('Example');
	}
}		