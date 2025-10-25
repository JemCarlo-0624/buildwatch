// Budget Review Modal Functions

function openBudgetReview(proposalId) {
  const modal = document.getElementById("budgetReviewModal")
  const content = document.getElementById("budgetReviewContent")

  // Show loading state
  content.innerHTML = '<div class="loading-spinner"></div> Loading budget details...'
  modal.classList.add("active")

  // Fetch budget data
  fetch(`fetch_budget_review.php?proposal_id=${proposalId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        renderBudgetReview(data.budget, data.breakdowns, proposalId)
      } else {
        content.innerHTML = `<div class="error-message">Error: ${data.error}</div>`
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      content.innerHTML = '<div class="error-message">Failed to load budget details</div>'
    })
}

function closeBudgetReview() {
  const modal = document.getElementById("budgetReviewModal")
  modal.classList.remove("active")
}

function renderBudgetReview(budget, breakdowns, proposalId) {
  const content = document.getElementById("budgetReviewContent")

  const proposedBudget = Number.parseFloat(budget.proposed_budget) || 0
  const adminBudget = Number.parseFloat(budget.admin_evaluation) || 0
  const difference = adminBudget - proposedBudget
  const differenceClass = difference > 0 ? "positive" : ""

  let breakdownHTML = ""
  if (breakdowns && breakdowns.length > 0) {
    breakdownHTML = `
            <div class="breakdown-section">
                <div class="breakdown-title">Budget Breakdown</div>
                <table class="breakdown-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th class="breakdown-cost">Estimated Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${breakdowns
                          .map(
                            (item) => `
                            <tr>
                                <td>${escapeHtml(item.item_name)}</td>
                                <td><span class="breakdown-category">${escapeHtml(item.category)}</span></td>
                                <td class="breakdown-cost">₱${Number.parseFloat(item.estimated_cost).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            </tr>
                        `,
                          )
                          .join("")}
                    </tbody>
                </table>
            </div>
        `
  }

  let commentHTML = ""
  if (budget.admin_comment) {
    commentHTML = `
            <div class="admin-comment">
                <div class="admin-comment-label">Admin's Comment</div>
                <div class="admin-comment-text">${escapeHtml(budget.admin_comment)}</div>
            </div>
        `
  }

  content.innerHTML = `
        <div class="budget-header">
            <div class="budget-title">${escapeHtml(budget.title)}</div>
            <div class="budget-description">${escapeHtml(budget.description)}</div>
        </div>

        <div class="budget-comparison">
            <div class="budget-box">
                <div class="budget-box-label">Your Proposed Budget</div>
                <div class="budget-box-value">₱${proposedBudget.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
            </div>
            <div class="budget-box">
                <div class="budget-box-label">Admin Evaluated Budget</div>
                <div class="budget-box-value">₱${adminBudget.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                <div class="budget-difference ${differenceClass}">
                    ${difference > 0 ? "+" : ""}₱${Math.abs(difference).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </div>
            </div>
        </div>

        ${breakdownHTML}
        ${commentHTML}

        <div class="decision-actions">
            <button class="btn-accept-budget" onclick="submitBudgetDecision(${proposalId}, 'accept')">
                <i class="fas fa-check"></i> Accept Budget
            </button>
            <button class="btn-reject-budget" onclick="submitBudgetDecision(${proposalId}, 'reject')">
                <i class="fas fa-times"></i> Reject Proposal
            </button>
        </div>
    `
}

function submitBudgetDecision(proposalId, decision) {
  if (!confirm(`Are you sure you want to ${decision === "accept" ? "accept" : "reject"} this budget?`)) {
    return
  }

  const formData = new FormData()
  formData.append("proposal_id", proposalId)
  formData.append("decision", decision)

  const content = document.getElementById("budgetReviewContent")
  const originalContent = content.innerHTML

  // Show loading state
  content.innerHTML = '<div class="loading-spinner"></div> Processing your decision...'

  fetch("process_client_budget_decision.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        content.innerHTML = `<div class="success-message"><i class="fas fa-check-circle"></i> ${data.message}</div>`
        setTimeout(() => {
          closeBudgetReview()
          location.reload()
        }, 2000)
      } else {
        content.innerHTML = `<div class="error-message">Error: ${data.error}</div>`
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      content.innerHTML = `<div class="error-message">Failed to process your decision</div>`
    })
}

function escapeHtml(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  }
  return text.replace(/[&<>"']/g, (m) => map[m])
}

// Close modal when clicking outside
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("budgetReviewModal")
  if (modal) {
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeBudgetReview()
      }
    })
  }
})
