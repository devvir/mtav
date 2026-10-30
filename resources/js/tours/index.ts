import { appName } from '@/lib/app';
import { defineTour, type TourStep } from '@/lib/tour';
import { _ } from '@/lib/translations';

/**
 * Every guided tour, by page. Steps point at data-tour="…" marks of the page;
 * InfoTip's "Tell me more" starts them.
 */

/** Member app */
/**
 * The lottery page, for everyone: steps for admins and steps for families, each shown
 * where its part is on the page (someone in both roles walks through both)
 */
const lotteryIntro = (): TourStep[] => [
  {
    target: 'lottery.status',
    title: _('Where it stands'),
    body: _(
      'When the lottery happens, whether it is running, and once it has, which unit each family received.',
    ),
  },
  {
    target: 'lottery.status',
    title: _('How it decides'),
    body: _(
      'Each family ranks the units of its type. The lottery first makes the worst-off family as well off as possible, then the whole project as satisfied as possible; only ties are drawn at random.',
    ),
  },
];

const lotteryAdmin = (): TourStep[] => [
  {
    target: 'lottery.schedule',
    title: _('Its date'),
    body: _(
      'From this date on, the lottery can be run; until then, families can change their rankings. The description is what everyone reads at the top of this page.',
    ),
  },
  {
    target: 'lottery.readiness',
    title: _('Ready, type by type'),
    body: _(
      'Each type should have as many units as families. If not, you are asked to confirm: what is left over of each type goes into one last round among everyone left over.',
    ),
  },
  {
    target: 'lottery.run',
    title: _('Running it'),
    body: _(
      'Once its date arrives. Everyone sees it work, step by step; then each family finds its unit on this page, and everyone is told. The result is final.',
    ),
  },
  {
    target: 'lottery.runs',
    title: _('Undoing a run'),
    body: _(
      'Only in exceptional cases: invalidating clears every assignment and asks for a reason, which everyone in the project reads.',
    ),
  },
];

const lotteryFamily = (): TourStep[] => [
  {
    target: 'lottery.plan',
    title: _('On the plan'),
    body: _(
      'The units of your type stand out, each with its place in your ranking. Drag one from the plan onto your list to place it there.',
    ),
  },
  {
    target: 'ranking.list',
    title: _('Your preferences'),
    body: _(
      "These are the units of your family's type, most wanted first. The lottery gives every family a unit as high in its ranking as possible.",
    ),
  },
  {
    target: 'ranking.list',
    title: _('Reorder it'),
    body: _(
      'Drag a unit, or use its arrows, to move it up or down. Any member of your family can change it: it is one ranking for the whole family.',
    ),
  },
  {
    target: 'ranking.saving',
    title: _('No need to save'),
    body: _('Every change is saved as you make it, until the lottery runs.'),
  },
  {
    target: 'lottery.results',
    title: _('The results'),
    body: _('Every family and the unit it received, yours marked.'),
  },
];

defineTour('lottery', () => [...lotteryIntro(), ...lotteryAdmin(), ...lotteryFamily()]);

/** Its admin part on its own, from the (?) by "Running the lottery" */
defineTour('manage.lottery', lotteryAdmin);

defineTour('member.files', () => [
  {
    target: 'files.tabs',
    title: _('Kinds of files'),
    body: _('Photos and videos form the gallery; documents and audio have their own lists.'),
  },
  {
    target: 'files.share',
    title: _('Share yours'),
    body: _(
      'Upload one or several files at once, with a short description. Only people in the project can see them.',
    ),
  },
]);

defineTour('member.events', () => [
  {
    target: 'events.tabs',
    title: _('Upcoming and past'),
    body: _('Meetings, assemblies and activities of the project.'),
  },
  {
    target: 'events.list',
    title: _('Will you go?'),
    body: _('When an event asks, answer with one tap, so organizers know how many to expect.'),
  },
]);

/** Project admin area */
defineTour('manage.setup', () => [
  {
    target: 'setup.quick',
    title: _('What a project is made of'),
    body: _(
      'Its families, its people and its units, with how far each has come. Each card leads where you add them.',
    ),
  },
  {
    target: 'setup.checklist',
    title: _('From empty to the lottery'),
    body: _(
      'Follow these steps in order. Each one opens the page where you do it, and ticks itself once done.',
    ),
  },
  {
    target: 'setup.approval',
    title: _('Room for the project'),
    body: _(
      'A limited project has room for most cooperatives. Should it need more, ask: the {app} team can make it unlimited.',
      { app: appName() },
    ),
  },
]);

defineTour('manage.unit_types', () => [
  {
    target: 'unit_types.add',
    title: _('Start with the types'),
    body: _(
      'A unit type groups equivalent units, like "2 bedrooms". Each family receives a unit of its own type.',
    ),
  },
  {
    target: 'unit_types.list',
    title: _('Keep them balanced'),
    body: _(
      'Each type should have as many units as families. Types that are not balanced are flagged here.',
    ),
  },
]);

defineTour('manage.units', () => [
  {
    target: 'units.identifier',
    title: _('Name the unit'),
    body: _('Use the identifier people already know: a number, or a block and floor.'),
  },
  {
    target: 'units.type',
    title: _('Its type'),
    body: _('Pick its type, or create a new type right here if it does not exist yet.'),
  },
  {
    target: 'units.another',
    title: _('Many at once'),
    body: _(
      '"Add and continue" saves this unit and leaves the form ready for the next one, with the same type.',
    ),
  },
]);

defineTour('manage.invitations', () => [
  {
    target: 'invite.email',
    title: _('Who to invite'),
    body: _(
      'The person receives an email with a link, valid for two weeks. Without an account, they choose a password; with one, they join after signing in.',
    ),
  },
  {
    target: 'invite.family',
    title: _('Into which family'),
    body: _(
      'Members always belong to a family. Their family can then invite the rest of its people by itself.',
    ),
  },
  {
    target: 'invite.admin',
    title: _('Fellow admins'),
    body: _(
      'Admins help configure the project and run the lottery. An admin may also belong to a family.',
    ),
  },
  {
    target: 'invite.pending',
    title: _('Waiting for an answer'),
    body: _('Invitations not accepted yet. You can withdraw them; the link then stops working.'),
  },
]);

defineTour('manage.plan', () => [
  {
    target: 'plan.toolbar',
    title: _('Add shapes'),
    body: _(
      'Units appear here by themselves. Add buildings, streets, green areas and other shapes to complete the picture.',
    ),
  },
  {
    target: 'plan.canvas',
    title: _('Draw'),
    body: _(
      'Drag a shape to move it. Its square corners resize it; its round handles move its corners. Double-click an edge to add a corner.',
    ),
  },
  {
    target: 'plan.floors',
    title: _('Floors'),
    body: _('Spread units over floors; each floor is drawn on its own.'),
  },
  {
    target: 'plan.save',
    title: _('Saved as you go'),
    body: _(
      'Changes save by themselves a moment after you make them. Undo and redo are above the canvas, and Ctrl+Z works too, even after a reload.',
    ),
  },
]);

/** Platform console */
defineTour('platform.approvals', () => [
  {
    target: 'approvals.tabs',
    title: _('Projects asking for approval'),
    body: _(
      'Projects started by anyone work within limits until approved. Those asking for approval come first.',
    ),
  },
  {
    target: 'approvals.list',
    title: _('Approving'),
    body: _(
      'Approve a project when you trust its admins: its limits are lifted and its people are told.',
    ),
  },
]);

defineTour('member.home', () => [
  {
    target: 'home.figures',
    title: _('The project in figures'),
    body: _('Its families, people and units: each leads to its page.'),
  },
  {
    target: 'home.lottery',
    title: _('The lottery'),
    body: _('When it happens, and once it has, which unit your family received.'),
  },
]);

defineTour('member.families', () => [
  {
    target: 'people.views',
    title: _('By family, or everyone'),
    body: _('See the cooperative family by family, or every person at once.'),
  },
]);

defineTour('member.people', () => [
  {
    target: 'people.views',
    title: _('By family, or everyone'),
    body: _('See the cooperative family by family, or every person at once.'),
  },
]);

/** Account */
defineTour('account.notifications', () => [
  {
    target: 'notifications.list',
    title: _('What happened'),
    body: _(
      'Newest first, unread ones marked. Opening one takes you where it happened and marks it read.',
    ),
  },
  {
    target: 'notifications.unread',
    title: _('Only what is new'),
    body: _('Show only the unread ones, or mark them all as read at once.'),
  },
]);

defineTour('account.profile', () => [
  {
    target: 'profile.sections',
    title: _('Three parts'),
    body: _(
      'About you: your name and picture, as others see them. Password: how you sign in. Preferences: your language and the look of the app.',
    ),
  },
  {
    target: 'profile.picture',
    title: _('Your picture'),
    body: _('People recognize you by it across your projects. Pick a photo from your device.'),
  },
]);

defineTour('account.projects', () => [
  {
    target: 'projects.invitations',
    title: _('Invitations waiting for you'),
    body: _('Join or decline. Joining takes you into the project, with your family and role set.'),
  },
  {
    target: 'projects.list',
    title: _('Your projects'),
    body: _('Open one to go into it. You can take part in several.'),
  },
  {
    target: 'projects.start',
    title: _('Start your own'),
    body: _('Start a project for your cooperative: you become its first admin.'),
  },
]);

defineTour('account.directory', () => [
  {
    target: 'directory.search',
    title: _('Find a project'),
    body: _('Search by its name, its organization or what it is about.'),
  },
  {
    target: 'directory.list',
    title: _('Join or ask'),
    body: _(
      'Some projects let you in at once; in others the admins decide, and you hear back from them. You enter with no family: you create yours, or someone in it adds you.',
    ),
  },
]);

defineTour('account.start_project', () => [
  {
    target: 'start.form',
    title: _('About the project'),
    body: _(
      'Its name as its people call it, and what it is about. All of it can be changed later, in its settings.',
    ),
  },
  {
    target: 'start.room',
    title: _('Room for the project'),
    body: _(
      'Every project starts limited, with room for most cooperatives. The {app} team can make it unlimited when you ask.',
      { app: appName() },
    ),
  },
]);

/** Project admin area, continued */
defineTour('manage.settings', () => [
  {
    target: 'settings.form',
    title: _('What people see'),
    body: _('Its name, description and organization appear to everyone in the project.'),
  },
  {
    target: 'settings.listed',
    title: _('In the directory'),
    body: _(
      'Listed, others can find the project and join it: at once, or when the admins accept them. They enter with no family.',
    ),
  },
  {
    target: 'settings.export',
    title: _('Its lists'),
    body: _('Its people, families and units, as spreadsheets to keep or share.'),
  },
  {
    target: 'settings.delete',
    title: _('Deleting it'),
    body: _(
      'Removes the project and everything in it, for everyone. You are asked to type its name first.',
    ),
  },
]);

/** Member app, continued */
defineTour('member.invitations', () => [
  {
    target: 'family_invite.form',
    title: _('Who to invite'),
    body: _('Their email, and their name for the greeting. They join your family directly.'),
  },
  {
    target: 'family_invite.pending',
    title: _('Waiting for an answer'),
    body: _('Invitations not accepted yet. You can withdraw them; the link then stops working.'),
  },
]);

/** Platform console, continued */
defineTour('platform.users', () => [
  {
    target: 'users.search',
    title: _('Find an account'),
    body: _('Search by name or email.'),
  },
  {
    target: 'users.list',
    title: _('Each account'),
    body: _('How many projects it takes part in, since when, and whether its email is confirmed.'),
  },
]);

defineTour('platform.superadmins', () => [
  {
    target: 'superadmins.list',
    title: _('Who has the role'),
    body: _(
      'Each of them sees and manages every project. Remove the role once it is no longer needed.',
    ),
  },
  {
    target: 'superadmins.grant',
    title: _('Giving the role'),
    body: _('To an existing account, by its email, exactly as they sign in.'),
  },
]);

defineTour('platform.settings', () => [
  {
    target: 'platform_settings.lottery',
    title: _('How long a lottery takes'),
    body: _(
      'Everyone sees the lottery work, step by step, for at least this long. The result does not depend on it.',
    ),
  },
]);

defineTour('platform.deployments', () => [
  {
    target: 'deploy.environments',
    title: _('What runs where'),
    body: _(
      'Each environment and the release it runs. Rolling back returns to the one before; starting over brings back the demo data.',
    ),
  },
  {
    target: 'deploy.dev',
    title: _('Trying a branch'),
    body: _('Deploy any branch to dev. Every deploy starts it over, with the demo data.'),
  },
  {
    target: 'deploy.production',
    title: _('Releasing'),
    body: _('Production gets what staging runs, once approved on GitHub.'),
  },
  {
    target: 'deploy.runs',
    title: _('Latest runs'),
    body: _('Every deployment and how it went, linked to GitHub.'),
  },
]);

defineTour('platform.activity', () => [
  {
    target: 'activity.filters',
    title: _('Narrow it down'),
    body: _('By person or name, by project, and by kind: changes, or sign-ins.'),
  },
  {
    target: 'activity.list',
    title: _('What happened'),
    body: _(
      "Each change with what it changed, from what to what; a person's own details only by name. Who did it, when, and from where.",
    ),
  },
]);

defineTour('member.forum', () => [
  {
    target: 'forum.open',
    title: _('Start or join a conversation'),
    body: _('Open a topic for a question, an idea or news: everyone in the project is told.'),
  },
  {
    target: 'forum.topics',
    title: _('The topics'),
    body: _(
      'Pinned ones first, then those with the latest posts. Whoever wrote in a topic is told of each reply.',
    ),
  },
]);

defineTour('member.chat', () => [
  {
    target: 'chat.new',
    title: _('Talk with anyone in the project'),
    body: _('Pick one person for a private conversation, or several for a group with its name.'),
  },
  {
    target: 'chat.list',
    title: _('Your conversations'),
    body: _('The latest first; a dot marks those with something new. Nobody else reads them.'),
  },
  {
    target: 'chat.composer',
    title: _('Writing'),
    body: _('Enter sends; Shift and Enter start a new line. The others see it at once.'),
  },
]);
