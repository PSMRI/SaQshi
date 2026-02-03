const API = "/api/assessment/v1";

let checklist = [];
let index = 0;

/* ----------------------------
   LOAD AREAS OF CONCERN
---------------------------- */
async function loadConcerns() {
    const fty  = facilityType.value;
    const dept = department.value;

    const res = await fetch(`${API}/concerns.php?facility_type=${fty}&department_id=${dept}`);
    const data = await res.json();

    concern.innerHTML = "";
    data.data.forEach(c => {
        concern.innerHTML += `<option value="${c.concern_id}">${c.name}</option>`;
    });

    loadStandards();
}

/* ----------------------------
   LOAD STANDARDS
---------------------------- */
async function loadStandards() {
    const fty  = facilityType.value;
    const dept = department.value;
    const con  = concern.value;

    const res = await fetch(`${API}/standards.php?facility_type=${fty}&department_id=${dept}&concern_id=${con}`);
    const data = await res.json();

    standard.innerHTML = "";
    data.data.forEach(s => {
        standard.innerHTML += `<option value="${s.subtype_id}">${s.reference}</option>`;
    });

    loadChecklist();
}

/* ----------------------------
   LOAD CHECKLIST
---------------------------- */
async function loadChecklist() {

    const url = `${API}/checklist.php?facility_type=${facilityType.value}
        &department_id=${department.value}
        &concern_id=${concern.value}
        &subtype_id=${standard.value}`;

    const res = await fetch(url);
    const data = await res.json();

    checklist = data.data;
    index = 0;
    renderQuestion();
}

/* ----------------------------
   RENDER QUESTION
---------------------------- */
function renderQuestion() {
    if (!checklist.length) {
        question.innerText = "No checklist found";
        return;
    }

    const q = checklist[index];
    question.innerText = q.checkpoint;

    progress.innerText = `Question ${index + 1} of ${checklist.length}`;
}

/* ----------------------------
   SAVE RESPONSE
---------------------------- */
async function saveResponse() {

    const val = document.querySelector("input[name='f']:checked");
    if (!val) return alert("Select compliance");

    await fetch(`${API}/response/save.php`, {
        method: "POST",
        headers: { "Content-Type":"application/json" },
        body: JSON.stringify({
            csqa_id: checklist[index].csqa_id,
            compliance: val.value,
            facility_id: 1,
            department_id: department.value,
            assessment_period: 2024
        })
    });

    index++;
    if (index < checklist.length) {
        renderQuestion();
    } else {
        alert("Standard completed");
        loadStandards(); // auto load next standard
    }
}

/* ----------------------------
   EVENT BINDINGS
---------------------------- */
facilityType.onchange = loadConcerns;
department.onchange   = loadConcerns;
concern.onchange      = loadStandards;
standard.onchange     = loadChecklist;

loadConcerns();
