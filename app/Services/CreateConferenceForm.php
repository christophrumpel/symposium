<?php namespace SaveMyProposals\Services;

use Conference;
use SaveMyProposals\Exceptions\ValidationException;
use Validator;

class CreateConferenceForm
{
    private $rules = [
        'title' => ['required'],
        'description' => ['required'],
        'url' => ['required'],
        'starts_at' => ['date'],
        'ends_at' => ['date'],
        'cfp_starts_at' => ['date'],
        'cfp_ends_at' => ['date'],
    ];

    private $input;
    private $user;

    private function __construct($input, $user)
    {
        $this->input = $this->removeEmptyFields($input);
        $this->user = $user;
    }

    private function removeEmptyFields($input)
    {
        return array_filter($input);
    }

    public static function fillOut($input, $user)
    {
        return new self($input, $user);
    }

    public function complete()
    {
        $validation = Validator::make($this->input, $this->rules);

        if ($validation->fails()) {
            throw new ValidationException('Invalid input provided, see errors', $validation->errors());
        }

        return Conference::create(array_merge($this->input, [
            'author_id' => $this->user->id,
        ]));
    }
}