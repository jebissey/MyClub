export default class DomUtils {
    static getEl(id) {
        const el = document.getElementById(id);
        if (!el) console.warn(`Element #${id} not found`);
        return el;
    }

    static clearContainer(container) {
        if (container) container.innerHTML = "";
    }

    static show(el) {
        if (el) el.classList.remove("d-none");
    }

    static hide(el) {
        if (el) el.classList.add("d-none");
    }
}
