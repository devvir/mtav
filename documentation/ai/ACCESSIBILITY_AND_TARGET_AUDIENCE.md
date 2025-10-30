# Accessibility & Target Audience Guidelines

## Target Audience

The application is designed for:

- **Modest people with old devices**: May have older phones or computers with limited capabilities
- **Elderly users**: Limited exposure to web applications, need clear and simple interfaces
- **Users with disabilities**: Including visual impairments requiring larger text and high contrast
- **Non-tech-savvy users**: Need intuitive, straightforward interfaces without complexity

## Critical Design Principles

### Typography

- **Large base font sizes**: Prefer larger fonts even if they seem excessive to developers
- **Scale up on larger screens**: Font sizes should increase on desktop, not decrease
- **Readability over aesthetics**: Choose clarity over trendy small text
- **Line height**: Generous line-height for comfortable reading (1.5-1.75 for body text)
- **Letter spacing**: Consider slight positive tracking for improved legibility

### Accessibility Requirements

- **WCAG AA minimum**: All text must meet 4.5:1 contrast ratio (7:1 for AAA)
- **Large touch targets**: Minimum 44x44px for interactive elements (mobile)
- **Clear focus indicators**: Visible 2px+ focus rings, high contrast
- **Semantic HTML**: Proper heading hierarchy, landmarks, ARIA where needed
- **Keyboard navigation**: Full app functionality without mouse
- **Screen reader support**: Meaningful labels, alt text, ARIA labels

### Color & Contrast

- **High contrast**: Favor strong contrast over subtle design
- **Color independence**: Never rely on color alone to convey information
- **Dark theme consideration**: Must maintain accessibility in both themes
- **Preserve existing dark colors**:
  - Background: `hsl(210, 20%, 2%)` - NON-NEGOTIABLE
  - Sidebar: `hsl(30, 30%, 6%)` - NON-NEGOTIABLE
  - These work well for users who prefer reduced brightness

### Interaction Design

- **Clear affordances**: Buttons should look clickable, links should be obvious
- **Generous spacing**: Avoid cramped layouts, use whitespace liberally
- **Simple navigation**: Minimize cognitive load, clear hierarchy
- **Error prevention**: Clear labels, validation messages, confirmation dialogs
- **Forgiving interfaces**: Allow undo, show what will happen before actions

### Performance Considerations

- **Old device support**: Avoid heavy animations, large images
- **Progressive enhancement**: Core functionality works without JavaScript
- **Lazy loading**: Load data progressively to avoid overwhelming old devices
- **Minimal dependencies**: Keep bundle size small

## Design System Implementation Strategy

When refactoring the design system:

1. **Always test with larger fonts**: Use browser zoom to simulate visual impairment
2. **Test keyboard navigation**: Tab through every interface
3. **Test with screen readers**: Ensure meaningful content flow
4. **Test on old devices**: Check performance on slower hardware
5. **User testing**: If possible, test with actual target audience

## Typography Scale Reference

Current preferred scale (maintain or increase):

- **xs**: 12px - Use sparingly, only for non-critical metadata
- **sm**: 14px - Secondary information, captions
- **base**: 16px - Default body text (MINIMUM)
- **lg**: 18px - Emphasized content
- **xl**: 20px - Subheadings
- **2xl**: 24px - Section headings
- **3xl**: 30px - Page headings

**Note**: On larger screens (md, lg, xl breakpoints), these should scale UP, not down.

## Color Palette Requirements

When creating `_next` color palette:

- All combinations must meet WCAG AA (4.5:1 for text)
- Prefer AAA (7:1) where possible for body text
- Test with color blindness simulators
- Ensure semantic colors (success, warning, error) are distinguishable without color
- Maintain high contrast in dark theme

## Component Guidelines

- **Cards**: Clear borders or shadows for definition
- **Buttons**: High contrast, clear hover/focus states, min 44px height on mobile
- **Forms**: Large labels, clear error states, helpful placeholder text
- **Modals**: Clear close buttons, focus trapping, ESC key support
- **Navigation**: Clear current page indicator, logical order

## Testing Checklist

Before considering any component "done":

- [ ] Keyboard navigation works completely
- [ ] Focus indicators visible on all interactive elements
- [ ] Text contrast meets WCAG AA minimum
- [ ] Touch targets 44x44px on mobile
- [ ] Works with 200% browser zoom
- [ ] Screen reader announces meaningful content
- [ ] No color-only information conveyance
- [ ] Loading states clear for slow connections
- [ ] Error messages helpful and actionable

## References

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [Inclusive Design Principles](https://inclusivedesignprinciples.org/)
- [A11y Project Checklist](https://www.a11yproject.com/checklist/)

---

**Last Updated**: October 30, 2025
**Maintained By**: AI Assistant (GitHub Copilot)
