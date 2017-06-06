<?php

use App\Commands\CreateSubmission;
use App\Commands\DestroySubmission;
use App\Conference;
use App\Submission;
use Laracasts\TestDummy\Factory;

class SubmissionTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->disableExceptionHandling();
    }

    /** @test */
    function submitting_attaches_to_conference()
    {
        $user = Factory::create('user');
        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $user->id
        ]);
        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        $submission = dispatch(new CreateSubmission($conference->id, $talk->id));

        $this->assertTrue($conference->submissions->contains($submission));
    }

    /** @test */
    function un_submitting_deletes_submission()
    {
        $user = Factory::create('user');
        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $user->id
        ]);
        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        $submission = dispatch(new CreateSubmission($conference->id, $talk->id));

        dispatch(new DestroySubmission($conference->id, $talk->id));

        $this->assertFalse($conference->submissions->contains($submission));
    }

    /** @test */
    function un_submitting_deletes_only_this_conference_submission()
    {
        $user = Factory::create('user');

        $conference1 = Factory::create('conference');
        $conference2 = Factory::create('conference');

        $talk1 = Factory::create('talk', [
            'author_id' => $user->id
        ]);
        $talk1revision = Factory::create('talkRevision');
        $talk1->revisions()->save($talk1revision);

        $talk2 = Factory::create('talk', [
            'author_id' => $user->id
        ]);
        $talk2revision = Factory::create('talkRevision');
        $talk2->revisions()->save($talk2revision);

        $talk_1_submission = dispatch(new CreateSubmission($conference1->id, $talk1->id));
        $talk_2_submission = dispatch(new CreateSubmission($conference1->id, $talk2->id));
        dispatch(new DestroySubmission($conference1->id, $talk1->id));
        $this->assertTrue($conference1->submissions->contains($talk_2_submission));

        $talk_2_submission = dispatch(new CreateSubmission($conference2->id, $talk2->id));
        $talk_1_submission = dispatch(new CreateSubmission($conference2->id, $talk1->id));
        dispatch(new DestroySubmission($conference2->id, $talk1->id));
        $this->assertTrue($conference2->submissions->contains($talk_2_submission));
    }

    /** @test */
    function submits_current_revision_if_many()
    {
        $user = Factory::create('user');
        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $user->id
        ]);

        $oldRevision = Factory::create('talkRevision', [
            'created_at' => '1999-01-01 01:01:01'
        ]);
        $talk->revisions()->save($oldRevision);

        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        $submissions = dispatch(new CreateSubmission($conference->id, $talk->id));

        $this->assertTrue($conference->submissions->contains($submissions));
    }

    /** @test */
    function un_submitting_one_revision_of_many_works()
    {
        $user = Factory::create('user');
        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $user->id
        ]);

        $oldRevision = Factory::create('talkRevision', [
            'title' => 'oldie',
            'created_at' => '1999-01-01 01:01:01'
        ]);
        $talk->revisions()->save($oldRevision);

        $revision = Factory::create('talkRevision', [
            'title' => 'submitted i hope'
        ]);
        $talk->revisions()->save($revision);

        $submission = dispatch(new CreateSubmission($conference->id, $talk->id));

        $revision2 = Factory::create('talkRevision');
        $talk->revisions()->save($revision2);

        $this->assertTrue($conference->submissions->contains($submission));

        $submission = dispatch(new DestroySubmission($conference->id, $talk->id));

        $this->assertFalse($conference->submissions->contains($submission));
    }

    /** @test */
    function un_submitting_does_not_delete_conference()
    {
        $user = Factory::create('user');
        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $user->id
        ]);
        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        dispatch(new CreateSubmission($conference->id, $talk->id));

        dispatch(new DestroySubmission($conference->id, $talk->id));

        $this->assertEquals(1, Conference::find($conference->id)->count());
    }

    /** @test */
    function user_can_submit_talks_via_http()
    {
        $user = Factory::create('user');
        $this->be($user);

        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $user->id
        ]);
        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        $this->post('submissions', [
            'conferenceId' => $conference->id,
            'talkId' => $talk->id,
        ]);

        $this->assertTrue($conference->submissions->contains(Submission::first()));
    }

    /** @test */
    function user_cannot_submit_other_users_talk()
    {
        $this->enableExceptionHandling();

        $user = Factory::create('user');
        $this->be($user);
        $otherUser = Factory::create('user', [
            'email' => 'a@b.com'
        ]);

        $conference = Factory::create('conference');
        $talk = Factory::create('talk', [
            'author_id' => $otherUser->id
        ]);
        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        $this->post('submissions', [
            'conferenceId' => $conference->id,
            'talkId' => $talk->id,
        ]);

        $this->assertResponseStatus(404);
        $this->assertEquals(0, $conference->submissions->count());
        $this->assertFalse($conference->submissions->contains($revision));
    }

    /**
     * @test
     */
    function user_can_retrieve_their_submissions_of_a_particular_talk()
    {
        $user = Factory::create('user');
        $this->be($user);

        $conference = Factory::create('conference');

        $talk_1 = Factory::create('talk', ['author_id' => $user->id]);
        $revision_1 = Factory::create('talkRevision');
        $talk_1->revisions()->save($revision_1);

        $talk_2 = Factory::create('talk', ['author_id' => $user->id]);
        $revision_2 = Factory::create('talkRevision');
        $talk_2->revisions()->save($revision_2);

        dispatch(new CreateSubmission($conference->id, $talk_1->id));
        dispatch(new CreateSubmission($conference->id, $talk_2->id));

        $submissions = $revision_1->submissions()->get();

        $this->assertCount(1, $submissions);
        $this->assertEquals($conference->id, $submissions[0]->conference_id);
        $this->assertEquals($revision_1->id, $submissions[0]->talk_revision_id);
    }

    /**
     * @test
     */
    function user_can_retrieve_all_submissions_to_a_conference()
    {
        $user = Factory::create('user');
        $this->be($user);

        $conference = Factory::create('conference');

        $talk_1 = Factory::create('talk', ['author_id' => $user->id]);
        $revision_1 = Factory::create('talkRevision');
        $talk_1->revisions()->save($revision_1);

        $talk_2 = Factory::create('talk', ['author_id' => $user->id]);
        $revision_2 = Factory::create('talkRevision');
        $talk_2->revisions()->save($revision_2);

        $submission_1 = dispatch(new CreateSubmission($conference->id, $talk_1->id));
        $submission_2 = dispatch(new CreateSubmission($conference->id, $talk_2->id));

        $submissions_to_conference = $conference->submissions()->get();

        $this->assertCount(2, $submissions_to_conference);
        $this->assertEquals($revision_1->id, $submissions_to_conference[0]->talk_revision_id);
        $this->assertEquals($revision_2->id, $submissions_to_conference[1]->talk_revision_id);
    }

    /**
     * @test
     * @group foo
     */
    function user_can_mark_a_submission_as_accepted_via_http()
    {
        $user = Factory::create('user');
        $this->be($user);

        $conference = Factory::create('conference');

        $talk = Factory::create('talk', ['author_id' => $user->id]);
        $revision = Factory::create('talkRevision');
        $talk->revisions()->save($revision);

        dispatch(new CreateSubmission($conference->id, $talk->id));

        // make get request using id of submission that we want to mark as accepted

        // assert that the submission has been updated with `accepted` set to `true`
    }
}
