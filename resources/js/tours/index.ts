import { appName } from '@/lib/app';
import { defineTour } from '@/lib/tour';
import { _ } from '@/lib/translations';

/**
 * Every guided tour, by page. Steps point at data-tour="…" marks of the page;
 * InfoTip's "Show me how" starts them.
 */

/** Member app */
defineTour('member.ranking', () => [
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
    target: 'lottery.status',
    title: _('When it is decided'),
    body: _('The lottery runs on its date. Here you will see which unit each family received.'),
  },
]);

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

defineTour('manage.lottery', () => [
  {
    target: 'lottery.schedule',
    title: _('Set the date'),
    body: _(
      'Families can change their rankings until then. The lottery can run once the date arrives.',
    ),
  },
  {
    target: 'lottery.readiness',
    title: _('Check the numbers'),
    body: _(
      'Each type should have as many units as families. If not, you will be asked to confirm, and some families or units will be left over.',
    ),
  },
  {
    target: 'lottery.run',
    title: _('Run it'),
    body: _(
      'The lottery protects the worst-off family first, then satisfies everyone as much as possible. Ties are broken at random. The result is final.',
    ),
  },
  {
    target: 'lottery.runs',
    title: _('Undoing a run'),
    body: _(
      'Only in exceptional cases: invalidating clears every assignment and asks for a reason, which everyone in the project reads.',
    ),
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
