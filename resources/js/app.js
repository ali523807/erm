import './bootstrap';

import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

import DataTable from 'datatables.net-bs5';

window.DataTable = DataTable;

import Handlebars from "handlebars";

window.Handlebars = Handlebars;

import 'datatables.net-responsive-bs5';

import "./easyAjax.js"
import "./easyDelete.js"
import "./extendJquery.js"

import {createIcons, icons} from "lucide";

function initLandingNavigation() {
    const landingNav = document.querySelector(".landing-nav");
    const landingToggle = document.querySelector(".landing-nav-toggle");
    const landingMenu = document.querySelector("#landing-menu");

    if (!landingNav || !landingToggle || !landingMenu || landingToggle.dataset.initialized === "true") {
        return;
    }

    landingToggle.dataset.initialized = "true";

    const setOpen = (isOpen) => {
        landingNav.classList.toggle("is-open", isOpen);
        landingToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    };

    landingToggle.addEventListener("click", () => {
        setOpen(!landingNav.classList.contains("is-open"));
    });

    landingMenu.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => setOpen(false));
    });
}

document.addEventListener("DOMContentLoaded", function () {
    createIcons({icons});
    initLandingNavigation();
});

document.addEventListener('livewire:navigating', function () {
    $.fn.dataTable.tables({visible: true, api: true}).destroy();
});

document.addEventListener('livewire:navigated', function () {
    createIcons({icons});
    initLandingNavigation();

    const sidebarToggle = document.querySelector("#sidebar-toggle");
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            document.querySelector("#sidebar").classList.toggle("collapsed");
            document.querySelector("body").classList.toggle("sidebar-collapsed");
            document.querySelector("#sidebarBackdrop").classList.toggle("show");
        });
    }

    const sidebarBackdrop = document.querySelector("#sidebarBackdrop");
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener("click", function () {
            document.querySelector("#sidebar").classList.toggle("collapsed");
            document.querySelector("body").classList.toggle("sidebar-collapsed");
            document.querySelector("#sidebarBackdrop").classList.toggle("show");
        });
    }

    const themeToggle = document.querySelector(".theme-toggle");
    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            toggleLocalStorage();
            toggleRootClass();
        });
    }

    function toggleRootClass() {
        const current = document.documentElement.getAttribute('data-bs-theme');
        const inverted = current == 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', inverted);
    }

    function toggleLocalStorage() {
        if (isLight()) {
            localStorage.removeItem("light");
        } else {
            localStorage.setItem("light", "set");
        }
    }

    function isLight() {
        return localStorage.getItem("light");
    }

    if (isLight()) {
        toggleRootClass();
    }
});
