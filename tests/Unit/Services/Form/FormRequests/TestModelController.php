<?php

namespace Tests\Unit\Services\Form\FormRequests;

/**
 * Dummy controller for testing FormService with UPDATE forms.
 * FormService uses reflection on the controller's edit() method
 * to determine the route parameter name for UPDATE forms.
 */
class TestModelController
{
    public function edit(TestModel $testModel)
    {
        // Dummy method for reflection
    }
}
