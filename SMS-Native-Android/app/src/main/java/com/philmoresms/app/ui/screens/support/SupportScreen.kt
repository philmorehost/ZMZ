package com.philmoresms.app.ui.screens.support

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.*
import com.philmoresms.app.ui.components.*
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class SupportViewModel : ViewModel() {
    var tickets by mutableStateOf<List<SupportTicket>>(emptyList())
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var showCreate by mutableStateOf(false)
    var subject by mutableStateOf("")
    var message by mutableStateOf("")
    var creating by mutableStateOf(false)

    init { load() }

    fun load() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                tickets = RetrofitClient.apiService.getTickets().await().tickets ?: emptyList()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load tickets"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }

    fun createTicket() {
        error = null
        success = null
        creating = true
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.createTicket(subject, message).await()
                success = res.message ?: "Ticket created"
                subject = ""
                message = ""
                showCreate = false
                load()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to create ticket"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                creating = false
            }
        }
    }
}

@Composable
fun SupportScreen(navController: NavController) {
    val viewModel: SupportViewModel = viewModel()

    Scaffold(
        bottomBar = {
            FooterBar("Support") { label ->
                when (label) {
                    "Home" -> navController.navigate("home") { popUpTo("home") { inclusive = true } }
                    "SMS Report" -> navController.navigate("reports")
                }
            }
        }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding).background(Background)) {
            AppTopBar("Support")

            Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 8.dp), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text("Support Tickets", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                Button(onClick = { viewModel.showCreate = true }, colors = ButtonDefaults.buttonColors(containerColor = Primary)) {
                    Icon(Icons.Default.Add, contentDescription = null, tint = Color.White)
                    Spacer(Modifier.width(4.dp))
                    Text("New", color = Color.White)
                }
            }

            if (viewModel.showCreate) {
                Column(Modifier.padding(20.dp)) {
                    FintechInput("Subject", viewModel.subject, { viewModel.subject = it })
                    FintechInput("Message", viewModel.message, { viewModel.message = it })
                    if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp)
                    FintechButton(if (viewModel.creating) "Creating..." else "Submit Ticket", { viewModel.createTicket() })
                    TextButton(onClick = { viewModel.showCreate = false }) { Text("Cancel", color = TextSecondary) }
                }
            }

            when {
                viewModel.loading -> LoadingBox()
                else -> LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    items(viewModel.tickets) { ticket ->
                        Surface(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(14.dp),
                            color = Color.White,
                            shadowElevation = 1.dp,
                            onClick = { navController.navigate("ticket/${ticket.ticketId}") }
                        ) {
                            Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.HeadsetMic, contentDescription = null, tint = Primary)
                                Spacer(Modifier.width(12.dp))
                                Column(Modifier.weight(1f)) {
                                    Text(ticket.subject ?: "-", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                                    Text(ticket.updatedAt ?: "", fontSize = 12.sp, color = TextSecondary)
                                }
                                Text(ticket.status ?: "", fontSize = 12.sp, color = Primary, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }
        }
    }
}

class TicketDetailViewModel : ViewModel() {
    var ticket by mutableStateOf<SupportTicket?>(null)
    var messages by mutableStateOf<List<TicketMessage>>(emptyList())
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var reply by mutableStateOf("")
    var sending by mutableStateOf(false)
    private var ticketId: String = ""

    fun load(id: String) {
        ticketId = id
        loading = true
        error = null
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.getTicket(id).await()
                ticket = res.ticket
                messages = res.messages ?: emptyList()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load ticket"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }

    fun sendReply() {
        error = null
        sending = true
        viewModelScope.launch {
            try {
                RetrofitClient.apiService.replyTicket(ticketId, reply).await()
                reply = ""
                load(ticketId)
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to send reply"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                sending = false
            }
        }
    }
}

@Composable
fun TicketDetailScreen(navController: NavController, ticketId: String) {
    val viewModel: TicketDetailViewModel = viewModel()
    LaunchedEffect(ticketId) { viewModel.load(ticketId) }

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Ticket", onBack = { navController.popBackStack() })

        if (viewModel.loading) {
            LoadingBox()
        } else {
            LazyColumn(Modifier.weight(1f), contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                items(viewModel.messages) { msg ->
                    val isAdmin = (msg.isAdminReply ?: 0) == 1
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = if (isAdmin) Arrangement.Start else Arrangement.End) {
                        Surface(
                            shape = RoundedCornerShape(14.dp),
                            color = if (isAdmin) PrimaryLight else Primary
                        ) {
                            Column(Modifier.padding(12.dp)) {
                                Text(msg.message ?: "", color = if (isAdmin) TextPrimary else Color.White, fontSize = 14.sp)
                                Text(msg.createdAt ?: "", color = if (isAdmin) TextSecondary else Color.White.copy(alpha = 0.8f), fontSize = 11.sp, modifier = Modifier.padding(top = 4.dp))
                            }
                        }
                    }
                }
            }

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(horizontal = 16.dp))

            Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = viewModel.reply,
                    onValueChange = { viewModel.reply = it },
                    modifier = Modifier.weight(1f),
                    placeholder = { Text("Type a reply") },
                    shape = RoundedCornerShape(14.dp)
                )
                Spacer(Modifier.width(8.dp))
                Button(onClick = { viewModel.sendReply() }, enabled = !viewModel.sending, colors = ButtonDefaults.buttonColors(containerColor = Primary)) {
                    Icon(Icons.Default.Send, contentDescription = "Send", tint = Color.White)
                }
            }
        }
    }
}
