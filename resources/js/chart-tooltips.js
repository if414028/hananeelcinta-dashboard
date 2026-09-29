const marks = '[data-chart-label]';
const tooltip = document.createElement('div');
tooltip.id = 'dashboard-chart-tooltip';
tooltip.setAttribute('role', 'tooltip');
tooltip.hidden = true;
tooltip.className = 'chart-tooltip';
const label = document.createElement('div');
const value = document.createElement('strong');
tooltip.append(label, value);
document.body.append(tooltip);
let active = null;

function hide() {
    active?.removeAttribute('aria-describedby');
    active = null;
    tooltip.hidden = true;
}

function show(mark, event) {
    if (active !== mark) active?.removeAttribute('aria-describedby');
    active = mark;
    label.textContent = mark.dataset.chartLabel;
    value.textContent = mark.dataset.chartValue;
    mark.setAttribute('aria-describedby', tooltip.id);
    tooltip.hidden = false;
    const rect = mark.getBoundingClientRect();
    const x = event?.clientX ?? rect.left + rect.width / 2;
    const y = event?.clientY ?? rect.top;
    const width = tooltip.offsetWidth;
    const height = tooltip.offsetHeight;
    tooltip.style.left = `${Math.max(8, Math.min(x - width / 2, window.innerWidth - width - 8))}px`;
    tooltip.style.top = `${Math.max(8, y - height - 12 < 8 ? Math.min(y + 16, window.innerHeight - height - 8) : y - height - 12)}px`;
}

document.addEventListener('pointerover', (event) => {
    const mark = event.target.closest(marks);
    if (mark) show(mark, event);
});
document.addEventListener('pointermove', (event) => {
    const mark = event.target.closest(marks);
    if (mark && active) show(mark, event);
});
document.addEventListener('pointerout', (event) => {
    if (event.target.closest(marks) && !event.relatedTarget?.closest(marks)) hide();
});
document.addEventListener('focusin', (event) => {
    const mark = event.target.closest(marks);
    if (mark) show(mark);
});
document.addEventListener('focusout', (event) => {
    if (event.target.closest(marks)) hide();
});
document.addEventListener('click', (event) => {
    const mark = event.target.closest(marks);
    if (mark) show(mark, event);
    else hide();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') hide();
    if (['Enter', ' '].includes(event.key) && event.target.matches(marks)) {
        event.preventDefault();
        show(event.target);
    }
});
window.addEventListener('scroll', hide, true);
window.addEventListener('resize', hide);
